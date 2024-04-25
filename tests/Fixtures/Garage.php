<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Metadata\Access;
use Kcs\Serializer\Tests\Fixtures\Discriminator\Vehicle;

#[AccessType(Access\Type::Property)]
class Garage
{
    #[Type('array<'.Vehicle::class.'>')]
    public $vehicles;

    public function __construct($vehicles)
    {
        $this->vehicles = $vehicles;
    }
}
