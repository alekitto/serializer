<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\Groups;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Exclusion\SerializationGroupProviderInterface;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\Metadata\Access;

#[AccessType(Access\Type::Property)]
class GroupsProvider implements SerializationGroupProviderInterface
{
    #[Groups(['foo'])]
    #[Type('string')]
    private $foo;

    #[Groups(['foobar'])]
    #[Type('string')]
    private $foobar;

    #[Type(GroupsObject::class)]
    #[Groups(['foo'])]
    private $obj;

    public function __construct()
    {
        $this->foo = 'foo';
        $this->foobar = 'foobar';
        $this->obj = new GroupsObject();
    }

    public function getSerializationGroups(SerializationContext $context): iterable
    {
        $parent = $context->attributes->get('groups', []);
        $parent[] = 'foo';

        return $parent;
    }
}
