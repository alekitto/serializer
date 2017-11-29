<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation as Serializer;

/**
 * @Serializer\XmlRoot("price")
 * @Serializer\AccessType("property")
 */
class CurrencyAwarePrice
{
    /**
     * @Serializer\XmlAttribute
     * @Serializer\Type("string")
     */
    private $currency;

    /**
     * @Serializer\XmlValue
     * @Serializer\Type("double")
     */
    private $amount;

    public function __construct($amount, $currency = 'EUR')
    {
        $this->currency = $currency;
        $this->amount = $amount;
    }
}
