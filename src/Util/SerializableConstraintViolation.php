<?php

declare(strict_types=1);

namespace Kcs\Serializer\Util;

use Kcs\Serializer\Attribute\Immutable;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Attribute\Xml;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * @Xml\Root("violation")
 * @Immutable()
 */
#[Xml\Root('violation')]
#[Immutable]
final class SerializableConstraintViolation
{
    /**
     * @Type("string")
     * @Xml\Attribute()
     */
    #[Type('string')]
    #[Xml\Attribute()]
    private string $propertyPath;

    /** @Type("string") */
    #[Type('string')]
    private string $message;

    public function __construct(ConstraintViolationInterface $violation)
    {
        $this->propertyPath = $violation->getPropertyPath();
        $this->message = (string) $violation->getMessage();
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
