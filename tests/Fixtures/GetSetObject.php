<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\Immutable;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Metadata\Access;

#[AccessType(Access\Type::PublicMethod)]
class GetSetObject
{
    #[AccessType(Access\Type::Property)]
    #[Type('integer')]
    private $id = 1;

    #[Type('string')]
    private $name = 'Foo';

    #[Immutable]
    private $readOnlyProperty = 42;

    public function getId()
    {
        throw new \RuntimeException('This should not be called.');
    }

    public function getName()
    {
        return 'Johannes';
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getReadOnlyProperty()
    {
        return $this->readOnlyProperty;
    }
}
