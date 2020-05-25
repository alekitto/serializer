<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata;

use LogicException;

class VirtualPropertyMetadata extends PropertyMetadata
{
    /**
     * {@inheritdoc}
     */
    public function __construct(string $class, string $methodName)
    {
        if (0 === \strpos($methodName, 'get')) {
            $fieldName = \lcfirst(\substr($methodName, 3));
        } else {
            $fieldName = $methodName;
        }

        $this->class = $class;
        $this->name = $fieldName;
        $this->getter = $methodName;
        $this->readOnly = true;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($obj, $value): void
    {
        throw new LogicException('VirtualPropertyMetadata is immutable.');
    }

    /**
     * {@inheritdoc}
     */
    public function setAccessor(string $type, ?string $getter = null, ?string $setter = null): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function __wakeup(): void
    {
    }
}
