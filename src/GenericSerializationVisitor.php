<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use ArrayObject;
use BackedEnum;
use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\Exclusion\Behavior;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Type\Type;
use ReflectionEnum;
use SplStack;
use TypeError;

use function array_keys;
use function array_merge;
use function assert;
use function count;
use function is_array;
use function range;
use function sprintf;

class GenericSerializationVisitor extends AbstractVisitor
{
    private GraphNavigator $navigator;
    private SplStack $dataStack;

    /** @var array<string, mixed>|ArrayObject<string, mixed>|null */
    private mixed $root = null;
    private mixed $data = null;

    public function setNavigator(GraphNavigator|null $navigator = null): void
    {
        if ($navigator === null) {
            unset($this->navigator);
        } else {
            $this->navigator = $navigator;
        }

        $this->root = null;
        $this->dataStack = new SplStack();
    }

    public function visitNull(mixed $data, Type $type, Context $context): mixed
    {
        return $this->data = null;
    }

    public function visitString(mixed $data, Type $type, Context $context): string|null
    {
        return $this->data = (string) $data;
    }

    public function visitBoolean(mixed $data, Type $type, Context $context): bool
    {
        return $this->data = (bool) $data;
    }

    public function visitInteger(mixed $data, Type $type, Context $context): int|null
    {
        return $this->data = (int) $data;
    }

    public function visitDouble(mixed $data, Type $type, Context $context): float|null
    {
        return $this->data = (float) $data;
    }

    public function visitHash(mixed $data, Type $type, Context $context): mixed
    {
        $rs = [];
        $elementType = $this->getElementType($type);
        $onlyValues = $type->hasParam(0) && ! $type->hasParam(1);
        $isAssociative = ! $onlyValues && array_keys($data) !== range(0, count($data) - 1);

        foreach ($data as $k => $v) {
            $context->getMetadataStack()->pushIndexPath((string) $k);
            $v = $this->navigator->accept($v, $elementType, $context);
            $context->getMetadataStack()->popIndexPath();

            if ($v === null && ! $isAssociative && ! $context->shouldSerializeNull()) {
                continue;
            }

            if ($onlyValues) {
                $rs[] = $v;
            } else {
                $rs[$k] = $v;
            }
        }

        return $this->data = $rs;
    }

    /**
     * {@inheritDoc}
     */
    public function visitArray(mixed $data, Type $type, Context $context): array
    {
        $rs = [];
        $elementType = $this->getElementType($type);

        foreach ($data as $k => $v) {
            $context->getMetadataStack()->pushIndexPath((string) $k);
            $v = $this->navigator->accept($v, $elementType, $context);
            $context->getMetadataStack()->popIndexPath();

            if ($v === null && ! $context->shouldSerializeNull()) {
                continue;
            }

            $rs[] = $v;
        }

        return $this->data = $rs;
    }

    public function visitEnum(mixed $data, Type $type, Context $context): mixed
    {
        $reflection = new ReflectionEnum($data::class);
        $backingType = $reflection->getBackingType();
        if ($backingType !== null && (string) $backingType === 'int') {
            return $this->visitInteger($data->value, $type, $context);
        }

        return $this->visitString($data instanceof BackedEnum ? $data->value : $data->name, $type, $context);
    }

    public function visitObject(ClassMetadata $metadata, mixed $data, Type $type, Context $context, ObjectConstructorInterface|null $objectConstructor = null): mixed
    {
        $this->data = [];
        $metadataStack = $context->getMetadataStack();

        foreach ($metadata->getAttributesMetadata() as $propertyMetadata) {
            assert($propertyMetadata instanceof PropertyMetadata);
            $excluded = $context->isPropertyExcluded($propertyMetadata);
            if ($excluded && $propertyMetadata->onExclude === Behavior::Skip) {
                continue;
            }

            $metadataStack->push($propertyMetadata);
            $this->visitProperty($propertyMetadata, $excluded ? null : $data, $context);
            $metadataStack->pop();
        }

        return $this->data;
    }

    public function startVisiting(mixed &$data, Type $type, Context $context): void
    {
        $this->dataStack->push($this->data);
        $this->data = null;
    }

    public function endVisiting(mixed $data, Type $type, Context $context): mixed
    {
        $rs = $this->data;
        $this->data = $this->dataStack->pop();

        if ($this->root === null && $this->dataStack->count() === 0) {
            $this->root = $rs;
        }

        return $rs;
    }

    public function visitCustom(callable $handler, mixed $data, Type $type, Context $context): mixed
    {
        return $this->data = parent::visitCustom($handler, $data, $type, $context);
    }

    /** @return array<string, mixed>|ArrayObject<string, mixed>|null */
    public function getRoot(): mixed
    {
        return $this->root;
    }

    /** @param array<string, mixed>|ArrayObject<string, mixed> $data the passed data must be understood by whatever encoding function is applied later */
    public function setRoot(mixed $data): void
    {
        if ($data === null) {
            throw new TypeError(sprintf('Argument #1 passed to %s cannot be null', __METHOD__));
        }

        $this->root = $data;
    }

    public function getResult(): mixed
    {
        return $this->getRoot();
    }

    protected function visitProperty(PropertyMetadata $metadata, mixed $data, Context $context): mixed
    {
        $v = $data !== null ? $this->navigator->accept($metadata->getValue($data), $metadata->type, $context) : null;
        if ($v === null && ! $context->shouldSerializeNull()) {
            return null;
        }

        $k = $context->namingStrategy->translateName($metadata);

        if ($metadata->inline) {
            if (is_array($v)) {
                $this->data = array_merge($this->data, $v);
            }
        } else {
            $this->data[$k] = $v;
        }

        return null;
    }

    /**
     * Allows you to add additional data to the current object/root element.
     *
     * @param mixed $value This value must either be a regular scalar, or an array.
     *                     It must not contain any objects anymore.
     */
    protected function addData(string $key, mixed $value): void
    {
        if (isset($this->data[$key])) {
            throw new InvalidArgumentException(sprintf('There is already data for "%s".', $key));
        }

        $this->data[$key] = $value;
    }

    protected function setData(mixed $data): void
    {
        $this->data = $data;
    }

    /** @internal */
    protected function getData(): mixed
    {
        return $this->data;
    }
}
