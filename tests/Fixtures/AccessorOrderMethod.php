<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation as Serializer;

/**
 * @Serializer\AccessorOrder("custom",  custom = {"method", "b", "a"})
 * @Serializer\AccessType("property")
 */
#[Serializer\AccessorOrder('custom', custom: ['method', 'b', 'a'])]
#[Serializer\AccessType(Serializer\AccessType::PROPERTY)]
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
    #[Serializer\VirtualProperty()]
    #[Serializer\SerializedName('foo')]
    public function getMethod()
    {
        return 'c';
    }
}
