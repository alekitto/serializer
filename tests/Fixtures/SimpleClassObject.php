<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\Xml\Attribute;
use Kcs\Serializer\Annotation\Xml\Element;
use Kcs\Serializer\Annotation\Xml\XmlNamespace;
use Kcs\Serializer\Metadata\Access;


#[XmlNamespace(prefix: 'old_foo', uri: 'http://old.foo.example.org')]
#[XmlNamespace(prefix: 'foo', uri: 'http://foo.example.org')]
#[XmlNamespace(prefix: 'new_foo', uri: 'http://new.foo.example.org')]
#[AccessType(Access\Type::Property)]
class SimpleClassObject
{
    #[Type('string')]
    #[Attribute(namespace: 'http://old.foo.example.org')]
    public $foo;

    #[Type('string')]
    #[Element(namespace: 'http://foo.example.org')]
    public $bar;

    #[Type('string')]
    #[Element(namespace: 'http://new.foo.example.org')]
    public $moo;
}
