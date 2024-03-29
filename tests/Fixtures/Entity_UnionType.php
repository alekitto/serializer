<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;

/**
 * @AccessType("property")
 */
class Entity_UnionType
{
    public string | int | null $uninitialized;
}
