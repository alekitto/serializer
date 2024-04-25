<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute as Serializer;
use Kcs\Serializer\Metadata\Access;

#[Serializer\AccessorOrder(Access\Order::Custom, custom: ['c', 'd', 'a', 'b'])]
#[Serializer\AccessType(Access\Type::Property)]
class AccessorOrderChild extends AccessorOrderParent
{
    private $c = 'c';
    private $d = 'd';
}
