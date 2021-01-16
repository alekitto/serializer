<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\Discriminator;

use Kcs\Serializer\Annotation as Serializer;

/**
 * @Serializer\Discriminator(field = "type", map = {
 *    "car": "Kcs\Serializer\Tests\Fixtures\Discriminator\Car",
 *    "moped": "Kcs\Serializer\Tests\Fixtures\Discriminator\Moped",
 * }, groups = {
 *    "Default",
 *    "discriminator_group"
 * })
 * @Serializer\AccessType("property")
 */
#[Serializer\Discriminator(field: 'type', map: ['car' => Car::class, 'moped' => Moped::class], groups: ['Default', 'discriminator_group'])]
#[Serializer\AccessType(Serializer\AccessType::PROPERTY)]
abstract class Vehicle
{
    /** @Serializer\Type("integer") */
    public $km;

    public function __construct($km)
    {
        $this->km = (int) $km;
    }
}
