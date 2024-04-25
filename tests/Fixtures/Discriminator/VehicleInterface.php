<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\Discriminator;

use Kcs\Serializer\Attribute as Serializer;
use Kcs\Serializer\Metadata\Access;

#[Serializer\Discriminator(map: ['car' => Car::class, 'moped' => Moped::class], field: 'type')]
#[Serializer\AccessType(Access\Type::Property)]
interface VehicleInterface
{
}
