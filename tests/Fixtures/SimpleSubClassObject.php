<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\Xml\Element;
use Kcs\Serializer\Annotation\Xml\XmlNamespace;
use Kcs\Serializer\Metadata\Access;

#[XmlNamespace(uri: 'http://foo.example.org', prefix: 'old_foo')]
#[XmlNamespace(uri: 'http://better.foo.example.org', prefix: 'foo')]
#[AccessType(Access\Type::Property)]
class SimpleSubClassObject extends SimpleClassObject
{
    #[Type('string')]
    #[Element(namespace: 'http://better.foo.example.org')]
    public $moo;

    #[Type('string')]
    #[Element(namespace: 'http://foo.example.org')]
    public $baz;

    #[Type('string')]
    #[Element(namespace: 'http://new.foo.example.org')]
    public $qux;
}
