<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Groups;
use Kcs\Serializer\Annotation\Type;

/**
 * @AccessType("property")
 */
class InvalidGroupsObject
{
    /**
     * @Groups({"foo, bar"})
     * @Type("string")
     */
    private $foo;
}
