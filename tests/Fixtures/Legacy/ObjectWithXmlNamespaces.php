<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\Legacy;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\XmlAttribute;
use Kcs\Serializer\Annotation\XmlElement;
use Kcs\Serializer\Annotation\XmlNamespace;
use Kcs\Serializer\Annotation\XmlRoot;

/**
 * @XmlRoot("test-object", namespace="http://example.com/namespace")
 * @XmlNamespace(uri="http://example.com/namespace")
 * @XmlNamespace(uri="http://schemas.google.com/g/2005", prefix="gd")
 * @XmlNamespace(uri="http://www.w3.org/2005/Atom", prefix="atom")
 * @AccessType("property")
 */
class ObjectWithXmlNamespaces
{
    /**
     * @Type("string")
     * @XmlElement(namespace="http://purl.org/dc/elements/1.1/");
     */
    private $title;

    /**
     * @Type("DateTime")
     * @XmlAttribute
     */
    private $createdAt;

    /**
     * @Type("string")
     * @XmlAttribute(namespace="http://schemas.google.com/g/2005")
     */
    private $etag;

    /**
     * @Type("string")
     * @XmlElement(namespace="http://www.w3.org/2005/Atom")
     */
    private $author;

    /**
     * @Type("string")
     * @XmlAttribute(namespace="http://purl.org/dc/elements/1.1/");
     */
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
