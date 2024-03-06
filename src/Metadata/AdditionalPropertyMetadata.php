<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata;

use LogicException;

class AdditionalPropertyMetadata extends PropertyMetadata
{
    /**
     * {@inheritDoc}
     */
    public function __construct(string $class, string $name)
    {
        $this->class = $class;
        $this->name = $name;
        $this->immutable = true;

        $this->setType($class . '::' . $name);
    }

    public function getValue(object $obj): object
    {
        return $obj;
    }

    public function setValue(object $obj, mixed $value): void
    {
        throw new LogicException('AdditionalPropertyMetadata is immutable.');
    }

    public function setAccessor(string $type, string|null $getter = null, string|null $setter = null): void
    {
    }

    public function __wakeup(): void
    {
    }
}
