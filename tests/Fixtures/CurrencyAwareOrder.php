<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\Xml\Root;

/**
 * @Root("order")
 * @AccessType("property")
 */
class CurrencyAwareOrder
{
    /** @Type("Kcs\Serializer\Tests\Fixtures\CurrencyAwarePrice") */
    private $cost;

    public function __construct(CurrencyAwarePrice $price = null)
    {
        $this->cost = $price ?: new CurrencyAwarePrice(5);
    }
}
