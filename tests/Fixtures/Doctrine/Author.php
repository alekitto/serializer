<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use Kcs\Serializer\Annotation\SerializedName;

/** @ORM\Entity */
class Author
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     * @SerializedName("full_name")
     */
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
