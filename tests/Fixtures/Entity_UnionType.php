<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Metadata\Access;

#[AccessType(Access\Type::Property)]
class Entity_UnionType
{
    public string | int | null $uninitialized;
}
