<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Metadata\Access;

#[AccessType(Access\Type::Property)]
class VehicleInterfaceGarage
{
    #[Type("array<".Discriminator\VehicleInterface::class.">")]
    public $vehicles;

    public function __construct($vehicles)
    {
        $this->vehicles = $vehicles;
    }
}
