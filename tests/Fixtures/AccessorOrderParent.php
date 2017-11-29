<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation as Serializer;

/**
 * @Serializer\AccessorOrder("alphabetical")
 * @Serializer\AccessType("property")
 */
class AccessorOrderParent
{
    private $b = 'b';
    private $a = 'a';
}
