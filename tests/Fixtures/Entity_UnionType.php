<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Metadata\Access;

#[AccessType(Access\Type::Property)]
class Entity_UnionType
{
    public string | int | null $uninitialized;
}
