<?php declare(strict_types=1);

namespace Kcs\Serializer\Util;

use Kcs\Serializer\Annotation\ReadOnly;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\XmlAttribute;
use Kcs\Serializer\Annotation\XmlRoot;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * @XmlRoot("violation")
 * @ReadOnly()
 */
class SerializableConstraintViolation
{
    /**
     * @Type("string")
     * @XmlAttribute()
     *
     * @var string
     */
    private $propertyPath;

    /**
     * @Type("string")
     *
     * @var string
     */
    private $message;

    public function __construct(ConstraintViolationInterface $violation)
    {
        $this->propertyPath = $violation->getPropertyPath();
        $this->message = $violation->getMessage();
    }

    /**
     * @return string
     */
    public function getPropertyPath()
    {
        return $this->propertyPath;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}
