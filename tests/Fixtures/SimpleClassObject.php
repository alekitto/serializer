<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Attribute\Xml\Attribute;
use Kcs\Serializer\Attribute\Xml\Element;
use Kcs\Serializer\Attribute\Xml\XmlNamespace;
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
