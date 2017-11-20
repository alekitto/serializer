<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\Doctrine\SingleTableInheritance;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Teacher extends Person
{
}
