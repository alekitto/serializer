<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\XmlAttribute;
use Kcs\Serializer\Annotation\XmlRoot;
use Kcs\Serializer\Annotation\XmlValue;

/**
 * @XmlRoot("child")
 * @AccessType("property")
 */
class Person
{
    /**
     * @Type("string")
     * @XmlValue(cdata=false)
     */
    public $name;

    /**
     * @Type("integer")
     * @XmlAttribute
     */
    public $age;
}
