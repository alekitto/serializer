<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\Groups;
use Kcs\Serializer\Annotation\SerializedName;
use Kcs\Serializer\Annotation\Since;
use Kcs\Serializer\Annotation\Until;
use Kcs\Serializer\Annotation\VirtualProperty;

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
