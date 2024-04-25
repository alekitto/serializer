<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\Doctrine;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Kcs\Serializer\Attribute\SerializedName;

#[ORM\Entity]
class Author
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    protected $id;

    #[ORM\Column]
    #[SerializedName('full_name')]
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
