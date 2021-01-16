<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\Xml\Element;
use Kcs\Serializer\Annotation\Xml\XmlNamespace;

/**
 * @XmlNamespace(prefix="old_foo", uri="http://foo.example.org");
 * @XmlNamespace(prefix="foo", uri="http://better.foo.example.org");
 * @AccessType("property")
 */
#[XmlNamespace(prefix: 'old_foo', uri: 'http://foo.example.org')]
#[XmlNamespace(prefix: 'foo', uri: 'http://better.foo.example.org')]
#[AccessType(AccessType::PROPERTY)]
class SimpleSubClassObject extends SimpleClassObject
{
    /**
     * @Type("string")
     * @Element(namespace="http://better.foo.example.org")
     */
    #[Type('string')]
    #[Element(namespace: 'http://better.foo.example.org')]
    public $moo;

    /**
     * @Type("string")
     * @Element(namespace="http://foo.example.org")
     */
    #[Type('string')]
    #[Element(namespace: 'http://foo.example.org')]
    public $baz;

    /**
     * @Type("string")
     * @Element(namespace="http://new.foo.example.org")
     */
    #[Type('string')]
    #[Element(namespace: 'http://new.foo.example.org')]
    public $qux;
}
