<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\Exclude;
use Kcs\Serializer\Annotation\SerializedName;
use Kcs\Serializer\Annotation\VirtualProperty;

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
