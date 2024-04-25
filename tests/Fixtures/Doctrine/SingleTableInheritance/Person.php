<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\Doctrine\SingleTableInheritance;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: Types::STRING)]
#[ORM\DiscriminatorMap([
    'student' => Student::class,
    'teacher' => Teacher::class,
])]
abstract class Person
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    public function getId()
    {
        return $this->id;
    }
}
