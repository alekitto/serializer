<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation as Serializer;

/**
 * @Serializer\AccessType("property")
 */
class AuthorChild extends Author
{
    /** @Serializer\Type("bool") */
    private $is_child = true;
}
