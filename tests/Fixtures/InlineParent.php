<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation as Serializer;
use Kcs\Serializer\Annotation\Type;

/**
 * @Serializer\AccessorOrder("alphabetical")
 * @Serializer\AccessType("property")
 */
class InlineParent
{
    /**
     * @Type("string")
     */
    private $c = 'c';

    /**
     * @Type("string")
     */
    private $d = 'd';

    /**
     * @Serializer\Inline
     */
    private $child;

    public function __construct($child = null)
    {
        $this->child = $child ?: new InlineChild();
    }
}
