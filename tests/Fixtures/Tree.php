<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute as Serializer;
use Kcs\Serializer\Metadata\Access;

#[Serializer\AccessType(Access\Type::Property)]
class Tree
{
    #[Serializer\MaxDepth(10)]
    public $tree;

    public function __construct($tree)
    {
        $this->tree = $tree;
    }
}
