<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Type\Type;

use function array_key_exists;
use function assert;
use function gettype;
use function is_array;
use function Safe\sprintf;
use function var_export;

/**
 * Generic Deserialization Visitor.
 */
class GenericDeserializationVisitor extends GenericSerializationVisitor
{
    /**
     * {@inheritdoc}
     */
    public function visitHash($data, Type $type, Context $context)
    {
        if (! is_array($data)) {
            throw new RuntimeException(sprintf('Expected array, but got %s: %s', gettype($data), var_export($data, true)));
        }

        // If no further parameters were given, keys/values are just passed as is.
        if (! $type->hasParam(0)) {
            $this->setData($data);

            return $data;
        }

        switch ($type->countParams()) {
            case 1: // Array is a list.
                $listType = $type->getParam(0);

                $result = [];
                foreach ($data as $k => $v) {
                    $context->getMetadataStack()->pushIndexPath((string) $k);
                    $result[] = $context->accept($v, $listType);
                    $context->getMetadataStack()->popIndexPath();
                }

                $this->setData($result);

                return $result;

            case 2: // Array is a map.
                $keyType = $type->getParam(0);
                $entryType = $type->getParam(1);

                $result = [];
                foreach ($data as $k => $v) {
                    $context->getMetadataStack()->pushIndexPath((string) $k);
                    $result[$context->accept($k, $keyType)] = $context->accept($v, $entryType);
                    $context->getMetadataStack()->popIndexPath();
                }

                $this->setData($result);

                return $result;

            default:
                throw new RuntimeException(sprintf('Array type cannot have more than 2 parameters, but got %s.', var_export($type->getParams(), true)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function visitArray($data, Type $type, Context $context)
    {
        if (! is_array($data)) {
            throw new RuntimeException(sprintf('Expected array, but got %s: %s', gettype($data), var_export($data, true)));
        }

        $listType = $type->getParam(0);

        $result = [];
        foreach ($data as $k => $v) {
            $context->getMetadataStack()->pushIndexPath((string) $k);
            $result[] = $context->accept($v, $listType);
            $context->getMetadataStack()->popIndexPath();
        }

        $this->setData($result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function visitObject(
        ClassMetadata $metadata,
        $data,
        Type $type,
        Context $context,
        ?ObjectConstructorInterface $objectConstructor = null
    ) {
        assert($objectConstructor !== null);
        assert($context instanceof DeserializationContext);
        $object = $objectConstructor->construct($this, $metadata, $data, $type, $context);

        foreach ($context->getNonSkippedProperties($metadata) as $propertyMetadata) {
            assert($propertyMetadata instanceof PropertyMetadata);
            $context->getMetadataStack()->push($propertyMetadata);
            $v = $this->visitProperty($propertyMetadata, $data, $context);
            $context->getMetadataStack()->pop();

            $propertyMetadata->setValue($object, $v);
        }

        $this->setData($object);

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    protected function visitProperty(PropertyMetadata $metadata, $data, Context $context)
    {
        $name = $this->namingStrategy->translateName($metadata);

        if ($data === null || ! array_key_exists($name, $data)) {
            return null;
        }

        if ($metadata->type === null) {
            throw new RuntimeException(sprintf('You must define a type for %s::$%s.', $metadata->getReflection()->class, $metadata->name));
        }

        $v = $data[$name] !== null ? $context->accept($data[$name], $metadata->type) : null;
        $this->addData($name, $v);

        return $v;
    }
}
