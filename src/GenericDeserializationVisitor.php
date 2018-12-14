<?php declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Type\Type;

/**
 * Generic Deserialization Visitor.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class GenericDeserializationVisitor extends GenericSerializationVisitor
{
    /**
     * {@inheritdoc}
     */
    public function visitArray($data, Type $type, Context $context)
    {
        if (! \is_array($data)) {
            throw new RuntimeException(\sprintf('Expected array, but got %s: %s', \gettype($data), \json_encode($data)));
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
                foreach ($data as $v) {
                    $result[] = $context->accept($v, $listType);
                }

                $this->setData($result);

                return $result;

            case 2: // Array is a map.
                $keyType = $type->getParam(0);
                $entryType = $type->getParam(1);

                $result = [];
                foreach ($data as $k => $v) {
                    $result[$context->accept($k, $keyType)] = $context->accept($v, $entryType);
                }

                $this->setData($result);

                return $result;

            default:
                throw new RuntimeException(\sprintf('Array type cannot have more than 2 parameters, but got %s.', \json_encode($type->getParams())));
        }
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
        $object = $objectConstructor->construct($this, $metadata, $data, $type, $context);

        /** @var PropertyMetadata $propertyMetadata */
        foreach ($context->getNonSkippedProperties($metadata) as $propertyMetadata) {
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

        if (null === $data || ! \array_key_exists($name, $data)) {
            return null;
        }

        if (null === $metadata->type) {
            throw new RuntimeException(\sprintf('You must define a type for %s::$%s.', $metadata->getReflection()->class, $metadata->name));
        }

        $v = null !== $data[$name] ? $context->accept($data[$name], $metadata->type) : null;
        $this->addData($name, $v);

        return $v;
    }

    /**
     * {@inheritdoc}
     */
    public function getResult()
    {
        return $this->getRoot();
    }
}
