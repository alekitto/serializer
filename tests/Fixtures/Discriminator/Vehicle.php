<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\Discriminator;

use Kcs\Serializer\Annotation as Serializer;
use Kcs\Serializer\Metadata\Access;

#[Serializer\Discriminator(map: ['car' => Car::class, 'moped' => Moped::class], field: 'type', groups: ['Default', 'discriminator_group'])]
#[Serializer\AccessType(Access\Type::Property)]
abstract class Vehicle
{
    #[Serializer\Type('integer')]
    public $km;

    public function __construct($km)
    {
        $this->km = (int) $km;
    }
}
