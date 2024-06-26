<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Attribute\Xml\Attribute;
use Kcs\Serializer\Attribute\Xml\Element;
use Kcs\Serializer\Attribute\Xml\XmlNamespace;
use Kcs\Serializer\Attribute\Xml\Root;
use Kcs\Serializer\Metadata\Access;

#[Root('test-object', namespace: 'http://example.com/namespace')]
#[XmlNamespace(uri: 'http://example.com/namespace')]
#[XmlNamespace(uri: 'http://schemas.google.com/g/2005', prefix: 'gd')]
#[XmlNamespace(uri: 'http://www.w3.org/2005/Atom', prefix: 'atom')]
#[AccessType(Access\Type::Property)]
class ObjectWithXmlNamespaces
{
    #[Type('string')]
    #[Element(namespace: 'http://purl.org/dc/elements/1.1/')]
    private $title;

    #[Type(\DateTime::class)]
    #[Attribute()]
    private $createdAt;

    #[Type('string')]
    #[Attribute(namespace: 'http://schemas.google.com/g/2005')]
    private $etag;

    #[Type('string')]
    #[Element(namespace: 'http://www.w3.org/2005/Atom')]
    private $author;

    #[Type('string')]
    #[Attribute(namespace: 'http://purl.org/dc/elements/1.1/')]
    private $language;

    public function __construct($title, $author, \DateTime $createdAt, $language)
    {
        $this->title = $title;
        $this->author = $author;
        $this->createdAt = $createdAt;
        $this->language = $language;
        $this->etag = \sha1($this->createdAt->format(\DateTime::ISO8601));
    }
}
