<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Attribute\Xml\Root;
use Kcs\Serializer\Attribute\Xml\Value;
use Kcs\Serializer\Metadata\Access;

#[Root('price')]
#[AccessType(Access\Type::Property)]
class Price
{
    #[Type('double')]
    #[Value()]
    private $price;

    public function __construct($price)
    {
        $this->price = $price;
    }
}
