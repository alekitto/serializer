<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\XmlAttribute;
use Kcs\Serializer\Annotation\XmlElement;
use Kcs\Serializer\Annotation\XmlNamespace;

/**
 * @XmlNamespace(prefix="old_foo", uri="http://old.foo.example.org");
 * @XmlNamespace(prefix="foo", uri="http://foo.example.org");
 * @XmlNamespace(prefix="new_foo", uri="http://new.foo.example.org");
 * @AccessType("property")
 */
class SimpleClassObject
{
    /**
     * @Type("string")
     * @XmlAttribute(namespace="http://old.foo.example.org")
     */
    public $foo;

    /**
     * @Type("string")
     * @XmlElement(namespace="http://foo.example.org")
     */
    public $bar;

    /**
     * @Type("string")
     * @XmlElement(namespace="http://new.foo.example.org")
     */
    public $moo;
}
