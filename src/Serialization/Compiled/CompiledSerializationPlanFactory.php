<?php

declare(strict_types=1);

namespace Kcs\Serializer\Serialization\Compiled;

use Closure;
use Kcs\Serializer\Context;
use Kcs\Serializer\Metadata\Access\Type as AccessType;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;
use ReflectionException;

use function assert;
use function array_key_exists;
use function spl_object_id;

final class CompiledSerializationPlanFactory
{
    /** @var array<int, array<int, CompiledClassPlan>> */
    private array $plans = [];

    public function __construct(private readonly CompiledSerializationDescriptorCacheInterface|null $descriptorCache = null)
    {
    }

    public function getPlan(ClassMetadata $metadata, Context $context): CompiledClassPlan
    {
        $metadataId = spl_object_id($metadata);
        $namingId = spl_object_id($context->namingStrategy);
        if (isset($this->plans[$metadataId][$namingId])) {
            return $this->plans[$metadataId][$namingId];
        }

        $cacheKey = $this->getCacheKey($metadata, $context);
        $descriptor = $this->descriptorCache?->get($cacheKey);
        if ($descriptor !== null && $this->isDescriptorValid($descriptor, $metadata, $context)) {
            return $this->plans[$metadataId][$namingId] = $this->createPlanFromDescriptor($descriptor, $metadata);
        }

        $descriptor = $this->createDescriptor($metadata, $context);
        $this->descriptorCache?->save($cacheKey, $descriptor);

        return $this->plans[$metadataId][$namingId] = $this->createPlanFromDescriptor($descriptor, $metadata);
    }

    private function createDescriptor(ClassMetadata $metadata, Context $context): CompiledClassDescriptor
    {
        $properties = [];
        foreach ($metadata->getAttributesMetadata() as $propertyMetadata) {
            assert($propertyMetadata instanceof PropertyMetadata);

            $properties[] = new CompiledPropertyDescriptor(
                $propertyMetadata->name,
                $context->namingStrategy->translateName($propertyMetadata),
                $this->getNativeType($propertyMetadata),
                $propertyMetadata->inline,
            );
        }

        return new CompiledClassDescriptor($metadata->name, $context->namingStrategy::class, $properties);
    }

    private function createPlanFromDescriptor(CompiledClassDescriptor $descriptor, ClassMetadata $metadata): CompiledClassPlan
    {
        $metadataByName = [];
        foreach ($metadata->getAttributesMetadata() as $propertyMetadata) {
            assert($propertyMetadata instanceof PropertyMetadata);
            $metadataByName[$propertyMetadata->name] = $propertyMetadata;
        }

        $properties = [];
        $nativeOnly = true;
        foreach ($descriptor->properties as $propertyDescriptor) {
            $propertyMetadata = $metadataByName[$propertyDescriptor->name];
            if ($propertyDescriptor->nativeType === null || $propertyDescriptor->inline) {
                $nativeOnly = false;
            }

            $properties[] = new CompiledPropertyPlan(
                $propertyMetadata,
                $propertyDescriptor->serializedName,
                $propertyDescriptor->nativeType,
                $this->createReader($propertyMetadata),
            );
        }

        return new CompiledClassPlan($properties, $nativeOnly && $properties !== []);
    }

    private function isDescriptorValid(CompiledClassDescriptor $descriptor, ClassMetadata $metadata, Context $context): bool
    {
        if ($descriptor->className !== $metadata->name || $descriptor->namingStrategy !== $context->namingStrategy::class) {
            return false;
        }

        $metadataByName = [];
        foreach ($metadata->getAttributesMetadata() as $propertyMetadata) {
            assert($propertyMetadata instanceof PropertyMetadata);
            $metadataByName[$propertyMetadata->name] = $propertyMetadata;
        }

        if (count($descriptor->properties) !== count($metadataByName)) {
            return false;
        }

        foreach ($descriptor->properties as $propertyDescriptor) {
            if (! array_key_exists($propertyDescriptor->name, $metadataByName)) {
                return false;
            }

            $propertyMetadata = $metadataByName[$propertyDescriptor->name];
            if ($propertyDescriptor->nativeType !== $this->getNativeType($propertyMetadata)) {
                return false;
            }

            if ($propertyDescriptor->inline !== $propertyMetadata->inline) {
                return false;
            }
        }

        return true;
    }

    private function getCacheKey(ClassMetadata $metadata, Context $context): string
    {
        return $metadata->name . '|' . $context->namingStrategy::class;
    }

    /** @return Closure(object): mixed|null */
    private function createReader(PropertyMetadata $metadata): Closure|null
    {
        if ($metadata->accessorType !== AccessType::Property) {
            return null;
        }

        try {
            $reflection = $metadata->getReflection();
        } catch (ReflectionException) {
            return null;
        }

        if ($reflection->hasType()) {
            return null;
        }

        $property = $metadata->name;
        $reader = Closure::bind(static fn (object $object): mixed => $object->{$property}, null, $reflection->getDeclaringClass()->name);
        assert($reader !== null);

        return $reader;
    }

    private function getNativeType(PropertyMetadata $metadata): string|null
    {
        $type = $metadata->type;
        if ($type === null || $type->countParams() !== 0) {
            return null;
        }

        return match ($type->name) {
            'string' => 'string',
            'integer', 'int' => 'int',
            'boolean', 'bool' => 'bool',
            'double', 'float' => 'float',
            default => null,
        };
    }
}
