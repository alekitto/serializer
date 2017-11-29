<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation as Serializer;
use Kcs\Serializer\Annotation\Type;

/**
 * @Serializer\AccessType("property")
 */
class InlineChild
{
    /**
     * @Type("string")
     */
    private $a = 'a';

    /**
     * @Type("string")
     */
    private $b = 'b';
}
