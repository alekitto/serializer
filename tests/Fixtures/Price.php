<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\Xml\Root;
use Kcs\Serializer\Annotation\Xml\Value;

/**
 * @Root("price")
 * @AccessType("property")
 */
#[Root('price')]
#[AccessType(AccessType::PROPERTY)]
class Price
{
    /**
     * @Type("double")
     * @Value
     */
    #[Type('double')]
    #[Value()]
    private $price;

    public function __construct($price)
    {
        $this->price = $price;
    }
}
