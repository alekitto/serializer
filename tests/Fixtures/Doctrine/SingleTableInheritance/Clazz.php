<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\Doctrine\SingleTableInheritance;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Clazz
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    #[ORM\ManyToOne(targetEntity: Teacher::class)]
    private $teacher;

    #[ORM\ManyToMany(targetEntity: Student::class)]
    private $students;

    public function __construct(Teacher $teacher, array $students)
    {
        $this->teacher = $teacher;
        $this->students = new ArrayCollection($students);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTeacher()
    {
        return $this->teacher;
    }

    public function getStudents()
    {
        return $this->students;
    }
}
