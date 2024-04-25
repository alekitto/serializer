<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\Xml\Root;
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
