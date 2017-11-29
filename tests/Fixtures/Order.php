<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\XmlRoot;

/**
 * @XmlRoot("order")
 * @AccessType("property")
 */
class Order
{
    /** @Type("Kcs\Serializer\Tests\Fixtures\Price") */
    private $cost;

    public function __construct(Price $price = null)
    {
        $this->cost = $price ?: new Price(5);
    }
}
