<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Attribute\Xml\Attribute;
use Kcs\Serializer\Metadata\Access;

#[AccessType(Access\Type::Property)]
class ObjectWithNullXmlAttribute extends SimpleObject
{
    #[Attribute]
    #[Type('string')]
    private $nullAttribute = null;
}
