<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\Xml\Root;
use Kcs\Serializer\Metadata\Access;
use Kcs\Serializer\Tests\Fixtures\Price;

#[Root('order')]
#[AccessType(Access\Type::Property)]
class Order
{
    #[Type(Price::class)]
    private $cost;

    public function __construct(Price $price = null)
    {
        $this->cost = $price ?: new Price(5);
    }
}
