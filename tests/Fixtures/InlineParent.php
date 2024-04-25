<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute as Serializer;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Metadata\Access;

#[Serializer\AccessorOrder(Access\Order::Alphabetical)]
#[Serializer\AccessType(Access\Type::Property)]
class InlineParent
{
    #[Type('string')]
    private $c = 'c';

    #[Type('string')]
    private $d = 'd';

    #[Serializer\Inline]
    private $child;

    public function __construct($child = null)
    {
        $this->child = $child ?: new InlineChild();
    }
}
