<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\Accessor;
use Kcs\Serializer\Attribute\Immutable;
use Kcs\Serializer\Attribute\SerializedName;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Attribute\Xml\Root;

#[Root("author")]
#[Immutable]
class AuthorReadOnlyPerClass
{
    #[Immutable]
    #[SerializedName("id")]
    private $id;

    #[Type('string')]
    #[SerializedName("full_name")]
    #[Accessor('getName')]
    #[Immutable(false)]
    private $name;

    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}
