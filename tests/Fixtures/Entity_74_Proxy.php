<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;

/**
 * @AccessType("property")
 */
class Entity_74_Proxy extends Entity_74
{
    public string $notUnset;

    public function __construct()
    {
        unset($this->uninitialized);
    }

    public function __get($name)
    {
        return 42;
    }
}
