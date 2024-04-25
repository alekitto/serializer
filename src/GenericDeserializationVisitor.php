<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use BackedEnum;
use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Type\Type;
use UnitEnum;

use function array_key_exists;
use function assert;
use function gettype;
use function is_array;
use function is_subclass_of;
use function sprintf;
use function var_export;

/**
 * Generic Deserialization Visitor.
 */
class GenericDeserializationVisitor extends GenericSerializationVisitor
{
    /**
     * {@inheritDoc}
     */
    public function visitHash(mixed $data, Type $type, Context $context): array
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
     * {@inheritDoc}
     */
    public function visitArray(mixed $data, Type $type, Context $context): array
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

    public function visitEnum(mixed $data, Type $type, Context $context): UnitEnum|null
    {
        assert($type->metadata !== null);
        $enum = $type->metadata->getName();
        assert(is_subclass_of($enum, UnitEnum::class, true));

        if (is_subclass_of($enum, BackedEnum::class, true)) {
            $value = $enum::from($data);
        } else {
            $value = null;
            foreach ($enum::cases() as $case) {
                if ($case->name === $data) {
                    $value = $case;
                    break;
                }
            }

            if ($value === null) {
                throw new RuntimeException(sprintf('Invalid value "%s" for enum "%s"', (string) $data, $enum));
            }
        }

        $this->setData($value);

        return $value;
    }

    public function visitObject(
        ClassMetadata $metadata,
        mixed $data,
        Type $type,
        Context $context,
        ObjectConstructorInterface|null $objectConstructor = null,
    ): object {
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

    protected function visitProperty(PropertyMetadata $metadata, mixed $data, Context $context): mixed
    {
        $name = $context->namingStrategy->translateName($metadata);

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
