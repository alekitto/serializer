<?php

declare(strict_types=1);

namespace Kcs\Serializer\Serialization\Compiled;

use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Context;
use Kcs\Serializer\GraphNavigator;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;

use function assert;
use function class_exists;
use function is_array;
use function is_object;
use function iterator_to_array;

trait CompiledSerializationVisitorTrait
{
    private GraphNavigator|null $compiledNavigator = null;
    private CompiledSerializationPlanFactory $compiledPlanFactory;
    private int $compiledObjects = 0;
    private int $fallbackObjects = 0;
    private int $delegatedProperties = 0;

    /** @var array<string, int> */
    private array $delegationReasons = [];

    public function setNavigator(GraphNavigator|null $navigator = null): void
    {
        parent::setNavigator($navigator);

        $this->compiledNavigator = $navigator;
        $this->compiledPlanFactory ??= new CompiledSerializationPlanFactory();
    }

    public function visitObject(ClassMetadata $metadata, mixed $data, Type $type, Context $context, ObjectConstructorInterface|null $objectConstructor = null): mixed
    {
        if ($context->getExclusionStrategy() !== null) {
            ++$this->fallbackObjects;

            return parent::visitObject($metadata, $data, $type, $context, $objectConstructor);
        }

        $plan = $this->compiledPlanFactory->getPlan($metadata, $context);
        $result = $this->serializeObjectFromCompiledPlan($plan, $data, $context);
        ++$this->compiledObjects;
        $this->setData($result);

        return $result;
    }

    public function resetCompiledSerializationStats(): void
    {
        $this->compiledObjects = 0;
        $this->fallbackObjects = 0;
        $this->delegatedProperties = 0;
        $this->delegationReasons = [];
    }

    public function getCompiledSerializationStats(): CompiledSerializationStats
    {
        return new CompiledSerializationStats(
            $this->compiledObjects,
            $this->fallbackObjects,
            $this->delegatedProperties,
            $this->delegationReasons,
        );
    }

    /** @inheritDoc */
    public function visitIterable(iterable $data, Type $type, Context $context): mixed
    {
        $items = is_array($data) ? $data : iterator_to_array($data);
        $supported = false;
        $result = $this->serializeCompiledTypedArray($type, $items, $context, $supported);
        if ($supported) {
            $this->setData($result);

            return $result;
        }

        if ($type->countParams() === 1) {
            return parent::visitArray($items, $type, $context);
        }

        return parent::visitHash($items, $type, $context);
    }

    /** @return array<string, mixed> */
    private function serializeObjectFromCompiledPlan(CompiledClassPlan $plan, object $data, Context $context): array
    {
        $result = [];
        foreach ($plan->properties as $property) {
            $serialized = $this->serializeCompiledProperty($property, $data, $context);

            if ($serialized === null && ! $context->shouldSerializeNull()) {
                continue;
            }

            if ($property->metadata->inline) {
                if (is_array($serialized)) {
                    $result += $serialized;
                }

                continue;
            }

            $result[$property->serializedName] = $serialized;
        }

        return $result;
    }

    private function serializeCompiledProperty(CompiledPropertyPlan $property, object $data, Context $context): mixed
    {
        $value = $property->read($data);
        if ($value === null) {
            return null;
        }

        if ($property->nativeType !== null) {
            return match ($property->nativeType) {
                'string' => (string) $value,
                'int' => (int) $value,
                'bool' => (bool) $value,
                'float' => (float) $value,
                default => $value,
            };
        }

        $type = $property->metadata->type;
        if ($type === null) {
            return $this->delegateCompiledProperty($property, $value, $context, 'missing_type');
        }

        if (is_object($value) && $type->countParams() === 0 && class_exists($type->name)) {
            $supported = false;
            $serialized = $this->serializeCompiledTypedObject($type, $value, $context, $supported);
            if ($supported) {
                return $serialized;
            }

            return $this->delegateCompiledProperty($property, $value, $context, 'empty_object_plan');
        }

        if (is_array($value) && $type->name === 'array' && $type->countParams() > 0 && $type->countParams() <= 2) {
            $supported = false;
            $serialized = $this->serializeCompiledTypedArray($type, $value, $context, $supported);
            if ($supported) {
                return $serialized;
            }

            return $this->delegateCompiledProperty($property, $value, $context, 'unsupported_array_item');
        }

        return $this->delegateCompiledProperty($property, $value, $context, 'unsupported_type');
    }

    /** @return array<string, mixed>|null */
    private function serializeCompiledTypedObject(Type $type, object $value, Context $context, bool &$supported): array|null
    {
        $className = $type->name;
        /** @var class-string $className */
        $metadata = $context->getMetadataFactory()->getMetadataFor($className);
        assert($metadata instanceof ClassMetadata);

        $plan = $this->compiledPlanFactory->getPlan($metadata, $context);
        if ($plan->properties === []) {
            $supported = false;

            return null;
        }

        $supported = true;

        return $this->serializeObjectFromCompiledPlan($plan, $value, $context);
    }

    /** @param mixed[] $value */
    private function serializeCompiledTypedArray(Type $type, array $value, Context $context, bool &$supported): array|null
    {
        $supported = true;
        $elementType = $this->getElementType($type);
        if ($elementType === null) {
            $supported = false;

            return null;
        }

        $onlyValues = $type->hasParam(0) && ! $type->hasParam(1);
        $result = [];
        foreach ($value as $key => $item) {
            $itemSupported = false;
            $serialized = $this->serializeCompiledArrayItem($elementType, $item, $context, $itemSupported);
            if (! $itemSupported) {
                $supported = false;

                return null;
            }

            if ($serialized === null && ! $context->shouldSerializeNull()) {
                continue;
            }

            if ($onlyValues) {
                $result[] = $serialized;
            } else {
                $result[$key] = $serialized;
            }
        }

        return $result;
    }

    private function serializeCompiledArrayItem(Type $type, mixed $item, Context $context, bool &$supported): mixed
    {
        $supported = true;
        if ($item === null) {
            return null;
        }

        if ($type->countParams() === 0) {
            $nativeType = match ($type->name) {
                'string' => 'string',
                'integer', 'int' => 'int',
                'boolean', 'bool' => 'bool',
                'double', 'float' => 'float',
                default => null,
            };

            if ($nativeType !== null) {
                return $this->castNativeValue($nativeType, $item);
            }

            if (is_object($item) && class_exists($type->name)) {
                return $this->serializeCompiledTypedObject($type, $item, $context, $supported);
            }
        }

        $supported = false;

        return null;
    }

    private function castNativeValue(string $nativeType, mixed $value): mixed
    {
        if ($nativeType === 'string') {
            return (string) $value;
        }

        if ($nativeType === 'int') {
            return (int) $value;
        }

        if ($nativeType === 'bool') {
            return (bool) $value;
        }

        if ($nativeType === 'float') {
            return (float) $value;
        }

        return $value;
    }

    private function delegateCompiledProperty(CompiledPropertyPlan $property, mixed $value, Context $context, string $reason): mixed
    {
        ++$this->delegatedProperties;
        $key = $property->metadata->class . '::$' . $property->metadata->name . ':' . $reason;
        $this->delegationReasons[$key] = ($this->delegationReasons[$key] ?? 0) + 1;

        if ($this->compiledNavigator === null) {
            ++$this->fallbackObjects;

            return null;
        }

        $metadataStack = $context->getMetadataStack();
        $metadataStack->push($property->metadata);

        try {
            return $this->compiledNavigator->accept($value, $property->metadata->type, $context);
        } finally {
            $metadataStack->pop();
        }
    }
}
