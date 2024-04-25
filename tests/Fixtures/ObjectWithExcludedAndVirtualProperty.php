<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\Exclude;
use Kcs\Serializer\Attribute\SerializedName;
use Kcs\Serializer\Attribute\VirtualProperty;

class ObjectWithExcludedAndVirtualProperty
{
    /**
     * @var int
     */
    #[Exclude]
    private $foo = 1;

    /**
     * NOTE: This method should NOT have a return type defined.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('foo')]
    public function getFoo()
    {
        return 'this is a foo string';
    }
}
