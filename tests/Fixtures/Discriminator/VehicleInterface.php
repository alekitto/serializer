<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\Discriminator;

use Kcs\Serializer\Annotation as Serializer;

/**
 * @Serializer\Discriminator(field = "type", map = {
 *    "car": "Kcs\Serializer\Tests\Fixtures\Discriminator\Car",
 *    "moped": "Kcs\Serializer\Tests\Fixtures\Discriminator\Moped",
 * })
 * @Serializer\AccessType("property")
 */
#[Serializer\Discriminator(field: 'type', map: [
    'car' => Car::class,
    'moped' => Moped::class,
])]
#[Serializer\AccessType(Serializer\AccessType::PROPERTY)]
interface VehicleInterface
{
}
