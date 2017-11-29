<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\XmlElement;
use Kcs\Serializer\Annotation\XmlNamespace;

/**
 * @XmlNamespace(prefix="old_foo", uri="http://foo.example.org");
 * @XmlNamespace(prefix="foo", uri="http://better.foo.example.org");
 * @AccessType("property")
 */
class SimpleSubClassObject extends SimpleClassObject
{
    /**
     * @Type("string")
     * @XmlElement(namespace="http://better.foo.example.org")
     */
    public $moo;

    /**
     * @Type("string")
     * @XmlElement(namespace="http://foo.example.org")
     */
    public $baz;

    /**
     * @Type("string")
     * @XmlElement(namespace="http://new.foo.example.org")
     */
    public $qux;
}
