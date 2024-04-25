<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute as Serializer;
use Kcs\Serializer\Metadata\Access;

#[Serializer\AccessorOrder(Access\Order::Alphabetical)]
#[Serializer\AccessType(Access\Type::Property)]
class AccessorOrderParent
{
    private $b = 'b';
    private $a = 'a';
}
