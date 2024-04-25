<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\Groups;
use Kcs\Serializer\Attribute\SerializedName;
use Kcs\Serializer\Attribute\Since;
use Kcs\Serializer\Attribute\Until;
use Kcs\Serializer\Attribute\VirtualProperty;

/**
 * dummy comment.
 */
class ObjectWithVersionedVirtualProperties
{
    #[VirtualProperty]
    #[SerializedName("low")]
    #[Groups(['versions'])]
    #[Until('8')]
    public function getVirualLowValue()
    {
        return 1;
    }

    #[VirtualProperty]
    #[SerializedName("high")]
    #[Groups(['versions'])]
    #[Since('6')]
    public function getVirualHighValue()
    {
        return 8;
    }
}
