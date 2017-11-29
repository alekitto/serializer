<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation as Serializer;

/**
 * @Serializer\AccessorOrder("custom",  custom = {"method", "b", "a"})
 * @Serializer\AccessType("property")
 */
class AccessorOrderMethod
{
    private $b = 'b';
    private $a = 'a';

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("foo")
     *
     * @return string
     */
    public function getMethod()
    {
        return 'c';
    }
}
