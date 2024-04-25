<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\SerializedName;
use Kcs\Serializer\Annotation\Since;
use Kcs\Serializer\Annotation\Until;
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
