<?php declare(strict_types=1);

namespace Kcs\Serializer\Util;

use Kcs\Serializer\Annotation\ReadOnly;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\Xml;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * @Xml\Root("violation")
 * @ReadOnly()
 */
#[Xml\Root('violation')]
#[ReadOnly()]
final class SerializableConstraintViolation
{
    /**
     * @Type("string")
     * @Xml\Attribute()
     */
    #[Type('string')]
    #[Xml\Attribute()]
    private string $propertyPath;

    /**
     * @Type("string")
     */
    #[Type('string')]
    private string $message;

    public function __construct(ConstraintViolationInterface $violation)
    {
        $this->propertyPath = $violation->getPropertyPath();
        $this->message = $violation->getMessage();
    }

    public function getPropertyPath(): string
    {
        return $this->propertyPath;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
