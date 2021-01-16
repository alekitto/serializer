<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\ExclusionPolicy;
use Kcs\Serializer\Annotation\VirtualProperty;

/**
 * @ExclusionPolicy("all")
 */
#[ExclusionPolicy(ExclusionPolicy::ALL)]
class ObjectWithVirtualPropertiesAndExcludeAll
{
    /**
     * @VirtualProperty
     */
    #[VirtualProperty()]
    public function getVirtualValue()
    {
        return 'value';
    }
}
