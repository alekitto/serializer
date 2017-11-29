<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata;

class StaticPropertyMetadata extends PropertyMetadata
{
    private $value;

    public function __construct($className, $fieldName, $fieldValue)
    {
        $this->class = $className;
        $this->name = $fieldName;
        $this->value = $fieldValue;
        $this->readOnly = true;
    }

    public function getValue($obj)
    {
        return $this->value;
    }

    public function setValue($obj, $value)
    {
        throw new \LogicException('StaticPropertyMetadata is immutable.');
    }

    public function setAccessor($type, $getter = null, $setter = null)
    {
    }

    public function __wakeup()
    {
    }

    public function __sleep()
    {
        return array_merge(parent::__sleep(), [
            'value',
        ]);
    }
}
