<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata;

use LogicException;

use function array_merge;

class StaticPropertyMetadata extends PropertyMetadata
{
    private mixed $value;

    /**
     * {@inheritDoc}
     *
     * @param mixed $fieldValue
     */
    public function __construct(string $class, string $fieldName, $fieldValue)
    {
        $this->class = $class;
        $this->name = $fieldName;
        $this->value = $fieldValue;
        $this->immutable = true;
    }

    public function getValue(object $obj): mixed
    {
        return $this->value;
    }

    public function setValue(object $obj, mixed $value): void
    {
        throw new LogicException('StaticPropertyMetadata is immutable.');
    }

    public function setAccessor(string $type, string|null $getter = null, string|null $setter = null): void
    {
    }

    public function __wakeup(): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function __sleep(): array
    {
        return array_merge(parent::__sleep(), ['value']);
    }
}
