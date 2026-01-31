<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Attribute\Xml\Root;
use Kcs\Serializer\Metadata\Access;

#[Root('order')]
#[AccessType(Access\Type::Property)]
class CurrencyAwareOrder
{
    #[Type(CurrencyAwarePrice::class)]
    private $cost;

    public function __construct(CurrencyAwarePrice|null $price = null)
    {
        $this->cost = $price ?: new CurrencyAwarePrice(5);
    }
}
