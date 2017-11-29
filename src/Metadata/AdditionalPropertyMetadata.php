<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata;

class AdditionalPropertyMetadata extends PropertyMetadata
{
    public function __construct($class, $name)
    {
        $this->class = $class;
        $this->name = $name;
        $this->readOnly = true;

        $this->setType($class.'::'.$name);
    }

    public function getValue($obj)
    {
        return $obj;
    }

    public function setValue($obj, $value)
    {
        throw new \LogicException('AdditionalPropertyMetadata is immutable.');
    }

    public function setAccessor($type, $getter = null, $setter = null)
    {
    }

    public function __wakeup()
    {
    }
}
