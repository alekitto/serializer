<?php

namespace JMS\Serializer\Util;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlAttribute;
use JMS\Serializer\Annotation\XmlRoot;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * @XmlRoot("violation")
 */
class SerializableConstraintViolation
{
    /**
     * @Type("string")
     * @XmlAttribute()
     *
     * @var string
     */
    private $property_path;

    /**
     * @Type("string")
     *
     * @var string
     */
    private $message;

    public function __construct(ConstraintViolationInterface $violation)
    {
        $this->property_path = $violation->getPropertyPath();
        $this->message = $violation->getMessage();
    }
}
