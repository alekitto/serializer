<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute as Serializer;
use Kcs\Serializer\Metadata\Access\Type;

#[Serializer\Xml\Root('price')]
#[Serializer\AccessType(Type::Property)]
class CurrencyAwarePrice
{
    #[Serializer\Xml\Attribute]
    #[Serializer\Type('string')]
    private $currency;

    #[Serializer\Xml\Value]
    #[Serializer\Type('double')]
    private $amount;

    public function __construct($amount, $currency = 'EUR')
    {
        $this->currency = $currency;
        $this->amount = $amount;
    }
}
