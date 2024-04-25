<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\Groups;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Metadata\Access;

#[AccessType(Access\Type::Property)]
class InvalidGroupsObject
{
    #[Groups(['foo, bar'])]
    #[Type('string')]
    private $foo;
}
