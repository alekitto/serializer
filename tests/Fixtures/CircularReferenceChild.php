<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Metadata\Access;

#[AccessType(Access\Type::Property)]
class CircularReferenceChild
{
    #[Type('string')]
    private $name;

    #[Type(CircularReferenceParent::class)]
    private $parent;

    public function __construct($name, CircularReferenceParent $parent)
    {
        $this->name = $name;
        $this->parent = $parent;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent(CircularReferenceParent $parent)
    {
        $this->parent = $parent;
    }
}
