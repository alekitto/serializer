<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\SerializedName;
use Kcs\Serializer\Attribute\Since;
use Kcs\Serializer\Attribute\Until;
use Kcs\Serializer\Metadata\Access;

#[AccessType(Access\Type::Property)]
class VersionedObject
{
    #[Until("1.0.0")]
    private $name;

    #[Since("1.0.1")]
    #[SerializedName("name")]
    private $name2;

    public function __construct($name, $name2)
    {
        $this->name = $name;
        $this->name2 = $name2;
    }
}
