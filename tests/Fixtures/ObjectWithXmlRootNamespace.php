<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Attribute\Xml;
use Kcs\Serializer\Metadata\Access;

#[Xml\Root('test-object', namespace: 'http://example.com/namespace')]
#[AccessType(Access\Type::Property)]
class ObjectWithXmlRootNamespace
{
    #[Type("string")]
    private $title;

    #[Type("DateTime")]
    #[Xml\Attribute]
    private $createdAt;

    #[Type("string")]
    #[Xml\Attribute]
    private $etag;

    #[Type("string")]
    private $author;

    #[Type("string")]
    #[Xml\Attribute]
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
