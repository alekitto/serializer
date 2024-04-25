<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\Xml\Root;
use Kcs\Serializer\Metadata\Access;

#[Root('order')]
#[AccessType(Access\Type::Property)]
class CurrencyAwareOrder
{
    #[Type(CurrencyAwarePrice::class)]
    private $cost;

    public function __construct(CurrencyAwarePrice $price = null)
    {
        $this->cost = $price ?: new CurrencyAwarePrice(5);
    }
}
