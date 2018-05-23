<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Groups;
use Kcs\Serializer\Annotation\SerializedName;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\VirtualProperty;

/**
 * @AccessType("property")
 */
class GroupsObject
{
    /**
     * @Groups({"foo"})
     * @Type("string")
     */
    private $foo;

    /**
     * @Groups({"foo","bar"})
     * @Type("string")
     */
    private $foobar;

    /**
     * @Groups({"bar", "Default"})
     * @Type("string")
     */
    private $bar;

    /**
     * @Groups({"foo", "!baz"})
     * @Type("string")
     *
     * @var string
     */
    private $baz;

    /**
     * @Type("string")
     */
    private $none;

    public function __construct()
    {
        $this->foo = 'foo';
        $this->bar = 'bar';
        $this->foobar = 'foobar';
        $this->none = 'none';
        $this->baz = 'baz';
    }

    /**
     * @Groups("baz")
     * @VirtualProperty()
     * @SerializedName("virt")
     */
    public function getVirtual1()
    {
        return 'virt_1';
    }

    /**
     * @Groups("!baz")
     * @VirtualProperty()
     * @SerializedName("virt")
     */
    public function getVirtual2()
    {
        return 'virt_2';
    }
}
