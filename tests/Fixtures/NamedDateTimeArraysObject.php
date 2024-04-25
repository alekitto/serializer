<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Attribute\Xml\KeyValuePairs;
use Kcs\Serializer\Metadata\Access;

#[AccessType(Access\Type::Property)]
class NamedDateTimeArraysObject
{
    /**
     * @var \DateTime[]
     */
    #[Type("array<string,DateTime<'d.m.Y H:i:s'>>")]
    #[KeyValuePairs]
    private $namedArrayWithFormattedDate;

    public function __construct($namedArrayWithFormattedDate)
    {
        $this->namedArrayWithFormattedDate = $namedArrayWithFormattedDate;
    }

    /**
     * @return \DateTime[]
     */
    public function getNamedArrayWithFormattedDate()
    {
        return $this->namedArrayWithFormattedDate;
    }
}
