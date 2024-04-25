<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Attribute\Xml\Root;
use Kcs\Serializer\Metadata\Access;

#[Root('person_location')]
#[AccessType(Access\Type::Property)]
class PersonLocation
{
    #[Type(Person::class)]
    public $person;

    #[Type("string")]
    public $location;
}
