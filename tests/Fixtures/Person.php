<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Attribute\Xml;
use Kcs\Serializer\Metadata\Access;

#[Xml\Root('child')]
#[AccessType(Access\Type::Property)]
class Person
{
    #[Type('string')]
    #[Xml\Value(cdata: false)]
    public $name;

    #[Type('integer')]
    #[Xml\Attribute()]
    public $age;
}
