<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\ExclusionPolicy;
use Kcs\Serializer\Attribute\VirtualProperty;
use Kcs\Serializer\Metadata\Exclusion;

/**
 * @ExclusionPolicy("all")
 */
#[ExclusionPolicy(Exclusion\Policy::All)]
class ObjectWithVirtualPropertiesAndExcludeAll
{
    #[VirtualProperty]
    public function getVirtualValue()
    {
        return 'value';
    }
}
