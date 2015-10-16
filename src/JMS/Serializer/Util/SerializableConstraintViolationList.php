<?php

namespace JMS\Serializer\Util;

use JMS\Serializer\Annotation\Inline;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class SerializableConstraintViolationList
{
    /**
     * @Type("array<JMS\Serializer\Util\SerializableConstraintViolation>")
     * @XmlList(entry="violation", inline=true)
     * @Inline()
     *
     * @var SerializableConstraintViolation[]
     */
    private $violations = [];

    public function __construct(ConstraintViolationListInterface $list)
    {
        foreach ($list as $violation) {
            $this->violations[] = new SerializableConstraintViolation($violation);
        }
    }
}
