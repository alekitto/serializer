<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\SerializedName;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Metadata\Access;

#[AccessType(Access\Type::Property)]
class SimpleObject
{
    #[Type("string")]
    private $foo;

    #[SerializedName("moo")]
    #[Type("string")]
    private $bar;

    #[Type("string")]
    protected $camelCase = 'boo';

    public function __construct($foo, $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }

    public function getFoo()
    {
        return $this->foo;
    }

    public function getBar()
    {
        return $this->bar;
    }
}
