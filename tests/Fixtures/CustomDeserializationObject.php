<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Metadata\Access;

#[AccessType(Access\Type::Property)]
class CustomDeserializationObject
{
    #[Type('string')]
    public $someProperty;

    public function __construct($value)
    {
        $this->someProperty = $value;
    }
}
