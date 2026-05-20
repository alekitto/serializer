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
use function spl_object_id;

final class CompiledSerializationPlanFactory
{
    /** @var array<int, array<int, CompiledClassPlan>> */
    private array $plans = [];

    public function getPlan(ClassMetadata $metadata, Context $context): CompiledClassPlan
    {
        $metadataId = spl_object_id($metadata);
        $namingId = spl_object_id($context->namingStrategy);
        if (isset($this->plans[$metadataId][$namingId])) {
            return $this->plans[$metadataId][$namingId];
        }

        $properties = [];
        $nativeOnly = true;
        foreach ($metadata->getAttributesMetadata() as $propertyMetadata) {
            assert($propertyMetadata instanceof PropertyMetadata);

            $nativeType = $this->getNativeType($propertyMetadata);
            if ($nativeType === null || $propertyMetadata->inline) {
                $nativeOnly = false;
            }

            $properties[] = new CompiledPropertyPlan(
                $propertyMetadata,
                $context->namingStrategy->translateName($propertyMetadata),
                $nativeType,
                $this->createReader($propertyMetadata),
            );
        }

        return $this->plans[$metadataId][$namingId] = new CompiledClassPlan($properties, $nativeOnly && $properties !== []);
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
