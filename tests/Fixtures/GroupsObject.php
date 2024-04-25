<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Groups;
use Kcs\Serializer\Annotation\SerializedName;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\VirtualProperty;
use Kcs\Serializer\Metadata\Access;

#[AccessType(Access\Type::Property)]
class GroupsObject
{
    #[Groups(['foo'])]
    #[Type('string')]
    private $foo;

    #[Groups(['foo', 'bar'])]
    #[Type('string')]
    private $foobar;

    #[Groups(['bar', 'Default'])]
    #[Type('string')]
    private $bar;

    /**
     * @var string
     */
    #[Groups(['foo', '!baz'])]
    #[Type('string')]
    private $baz;

    #[Type('string')]
    private $none;

    public function __construct()
    {
        $this->foo = 'foo';
        $this->bar = 'bar';
        $this->foobar = 'foobar';
        $this->none = 'none';
        $this->baz = 'baz';
    }

    #[Groups(['baz'])]
    #[VirtualProperty]
    #[SerializedName('virt')]
    public function getVirtual1()
    {
        return 'virt_1';
    }

    #[Groups(['!baz'])]
    #[VirtualProperty]
    #[SerializedName('virt')]
    public function getVirtual2()
    {
        return 'virt_2';
    }
}
