<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata;

use LogicException;

use function array_merge;

class StaticPropertyMetadata extends PropertyMetadata
{
    private $value;

    /**
     * {@inheritdoc}
     */
    public function __construct(string $className, string $fieldName, $fieldValue)
    {
        $this->class = $className;
        $this->name = $fieldName;
        $this->value = $fieldValue;
        $this->readOnly = true;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($obj)
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($obj, $value): void
    {
        throw new LogicException('StaticPropertyMetadata is immutable.');
    }

    public function setAccessor(string $type, ?string $getter = null, ?string $setter = null): void
    {
    }

    public function __wakeup(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function __sleep(): array
    {
        return array_merge(parent::__sleep(), ['value']);
    }
}
