<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation as Serializer;

/**
 * @Serializer\AccessType("property")
 */
class ObjectWithEmptyHash
{
    /**
     * @Serializer\Type("array<string,string>")
     */
    private $hash = [];
}
