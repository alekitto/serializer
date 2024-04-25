<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\Csv;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Csv;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Metadata\Access;
use Kcs\Serializer\Tests\Fixtures\Price;

#[Csv(keySeparator: "::")]
#[AccessType(Access\Type::Property)]
class KeySeparator
{
    #[Type(Price::class)]
    private $cost;

    public function __construct(Price $price = null)
    {
        $this->cost = $price ?: new Price(5);
    }
}
