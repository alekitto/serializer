<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Metadata\Access;

#[AccessType(Access\Type::Property)]
class DateTimeArraysObject
{
    /**
     * @var \DateTime[]
     */
    #[Type('array<DateTime>')]
    private $arrayWithDefaultDateTime;

    /**
     * @var \DateTime[]
     */
    #[Type('array<DateTime<\'d.m.Y H:i:s\'>>')]
    private $arrayWithFormattedDateTime;

    public function __construct($arrayWithDefaultDateTime, $arrayWithFormattedDateTime)
    {
        $this->arrayWithDefaultDateTime = $arrayWithDefaultDateTime;
        $this->arrayWithFormattedDateTime = $arrayWithFormattedDateTime;
    }

    /**
     * @return \DateTime[]
     */
    public function getArrayWithDefaultDateTime()
    {
        return $this->arrayWithDefaultDateTime;
    }

    /**
     * @return \DateTime[]
     */
    public function getArrayWithFormattedDateTime()
    {
        return $this->arrayWithFormattedDateTime;
    }
}
