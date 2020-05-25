<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\Xml;

/**
 * @Xml\Root("child")
 * @AccessType("property")
 */
class Person
{
    /**
     * @Type("string")
     * @Xml\Value(cdata=false)
     */
    public $name;

    /**
     * @Type("integer")
     * @Xml\Attribute()
     */
    public $age;
}
