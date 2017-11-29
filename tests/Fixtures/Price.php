<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\XmlRoot;
use Kcs\Serializer\Annotation\XmlValue;

/**
 * @XmlRoot("price")
 * @AccessType("property")
 */
class Price
{
    /**
     * @Type("double")
     * @XmlValue
     */
    private $price;

    public function __construct($price)
    {
        $this->price = $price;
    }
}
