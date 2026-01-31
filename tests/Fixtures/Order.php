<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Attribute\Xml\Root;
use Kcs\Serializer\Metadata\Access;
use Kcs\Serializer\Tests\Fixtures\Price;

#[Root('order')]
#[AccessType(Access\Type::Property)]
class Order
{
    #[Type(Price::class)]
    private $cost;

    public function __construct(Price|null $price = null)
    {
        $this->cost = $price ?: new Price(5);
    }
}
