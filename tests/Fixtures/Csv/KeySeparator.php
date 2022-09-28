<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\Csv;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Csv;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Tests\Fixtures\Price;

/**
 * @Csv(keySeparator="::")
 * @AccessType("property")
 */
#[Csv(keySeparator: "::")]
#[AccessType(AccessType::PROPERTY)]
class KeySeparator
{
    /** @Type("Kcs\Serializer\Tests\Fixtures\Price") */
    private $cost;

    public function __construct(Price $price = null)
    {
        $this->cost = $price ?: new Price(5);
    }
}
