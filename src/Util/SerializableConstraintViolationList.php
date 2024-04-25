<?php

declare(strict_types=1);

namespace Kcs\Serializer\Util;

use Kcs\Serializer\Attribute\Immutable;
use Kcs\Serializer\Attribute\Inline;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Attribute\Xml;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class SerializableConstraintViolationList
{
    /**
     * @Type("array<Kcs\Serializer\Util\SerializableConstraintViolation>")
     * @Xml\XmlList(entry="violation", inline=true)
     * @Inline()
     * @Immutable()
     * @var SerializableConstraintViolation[]
     */
    #[Type('array<Kcs\Serializer\Util\SerializableConstraintViolation>')]
    #[Xml\XmlList(entry: 'violation', inline: true)]
    #[Immutable]
    #[Inline]
    private array $violations = [];

    public function __construct(ConstraintViolationListInterface $list)
    {
        foreach ($list as $violation) {
            $this->violations[] = new SerializableConstraintViolation($violation);
        }
    }

    /** @return SerializableConstraintViolation[] */
    public function getViolations(): array
    {
        return $this->violations;
    }
}
