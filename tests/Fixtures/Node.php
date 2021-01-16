<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation as Serializer;

/**
 * @Serializer\AccessType("property")
 */
#[Serializer\AccessType(Serializer\AccessType::PROPERTY)]
class Node
{
    /**
     * @Serializer\MaxDepth(2)
     */
    #[Serializer\MaxDepth(2)]
    public $children;

    public $foo = 'bar';

    public function __construct($children = [])
    {
        $this->children = $children;
    }
}
