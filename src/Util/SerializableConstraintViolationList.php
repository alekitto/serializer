<?php declare(strict_types=1);

namespace Kcs\Serializer\Util;

use Kcs\Serializer\Annotation\Inline;
use Kcs\Serializer\Annotation\ReadOnly;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\Xml;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class SerializableConstraintViolationList
{
    /**
     * @Type("array<Kcs\Serializer\Util\SerializableConstraintViolation>")
     * @Xml\XmlList(entry="violation", inline=true)
     * @Inline()
     * @ReadOnly()
     *
     * @var SerializableConstraintViolation[]
     */
    #[Type('array<Kcs\Serializer\Util\SerializableConstraintViolation>')]
    #[Xml\XmlList(entry: 'violation', inline: true)]
    #[Inline()]
    #[ReadOnly()]
    private array $violations = [];

    public function __construct(ConstraintViolationListInterface $list)
    {
        foreach ($list as $violation) {
            $this->violations[] = new SerializableConstraintViolation($violation);
        }
    }

    /**
     * @return SerializableConstraintViolation[]
     */
    public function getViolations(): array
    {
        return $this->violations;
    }
}
