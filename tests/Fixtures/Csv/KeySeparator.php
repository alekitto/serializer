<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\Csv;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\Csv;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Metadata\Access;
use Kcs\Serializer\Tests\Fixtures\Price;

#[Csv(keySeparator: "::")]
#[AccessType(Access\Type::Property)]
class KeySeparator
{
    #[Type(Price::class)]
    private $cost;

    public function __construct(Price|null $price = null)
    {
        $this->cost = $price ?: new Price(5);
    }
}
