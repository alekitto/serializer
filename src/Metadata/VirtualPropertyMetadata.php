<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata;

use LogicException;

use function lcfirst;
use function Safe\substr;
use function strpos;

class VirtualPropertyMetadata extends PropertyMetadata
{
    /**
     * {@inheritdoc}
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

    /**
     * {@inheritdoc}
     */
    public function setValue(object $obj, mixed $value): void
    {
        throw new LogicException('VirtualPropertyMetadata is immutable.');
    }

    public function setAccessor(string $type, ?string $getter = null, ?string $setter = null): void
    {
    }

    public function __wakeup(): void
    {
    }
}
