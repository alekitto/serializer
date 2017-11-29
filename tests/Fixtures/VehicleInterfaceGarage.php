<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Type;

/**
 * @AccessType("property")
 */
class VehicleInterfaceGarage
{
    /**
     * @Type("array<Kcs\Serializer\Tests\Fixtures\Discriminator\VehicleInterface>")
     */
    public $vehicles;

    public function __construct($vehicles)
    {
        $this->vehicles = $vehicles;
    }
}
