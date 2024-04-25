<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata;

use LogicException;

use function lcfirst;
use function strpos;
use function substr;

class VirtualPropertyMetadata extends PropertyMetadata
{
    /**
     * {@inheritDoc}
     */
    public function __construct(string $class, string $methodName)
    {
        if (strpos($methodName, 'get') === 0) {
            $fieldName = lcfirst(substr($methodName, 3));
        } else {
            $fieldName = $methodName;
        }

        $this->class = $class;
        $this->name = $fieldName;
        $this->getter = $methodName;
        $this->immutable = true;
    }

    public function setValue(object $obj, mixed $value): void
    {
        throw new LogicException('VirtualPropertyMetadata is immutable.');
    }

    public function setAccessor(Access\Type $type, string|null $getter = null, string|null $setter = null): void
    {
    }

    public function __wakeup(): void
    {
    }
}
