<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation as Serializer;

/**
 * @Serializer\AccessorOrder("custom", custom = {"c", "d", "a", "b"})
 * @Serializer\AccessType("property")
 */
#[Serializer\AccessorOrder('custom', custom: ['c', 'd', 'a', 'b'])]
#[Serializer\AccessType(Serializer\AccessType::PROPERTY)]
class AccessorOrderChild extends AccessorOrderParent
{
    private $c = 'c';
    private $d = 'd';
}
