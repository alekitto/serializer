<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation as Serializer;

/**
 * @Serializer\AccessType("property")
 */
class Tree
{
    /**
     * @Serializer\MaxDepth(10)
     */
    public $tree;

    public function __construct($tree)
    {
        $this->tree = $tree;
    }
}
