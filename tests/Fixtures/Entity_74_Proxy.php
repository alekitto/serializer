<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\VirtualProperty;
use Kcs\Serializer\Metadata\Access;

#[AccessType(Access\Type::Property)]
class Entity_74_Proxy extends Entity_74
{
    public string $notUnset;
    public ?string $nullableString;

    public function __construct()
    {
        unset($this->uninitialized);
    }

    public function __get($name)
    {
        return 42;
    }

    #[VirtualProperty]
    public function getVirtualProperty(): string
    {
        return '';
    }
}
