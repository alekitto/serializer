<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute as Serializer;
use Kcs\Serializer\Metadata\Access;

#[Serializer\AccessorOrder(Access\Order::Custom, custom: ['method', 'b', 'a'])]
#[Serializer\AccessType(Access\Type::Property)]
class AccessorOrderMethod
{
    private $b = 'b';
    private $a = 'a';

    #[Serializer\VirtualProperty]
    #[Serializer\SerializedName('foo')]
    public function getMethod(): string
    {
        return 'c';
    }
}
