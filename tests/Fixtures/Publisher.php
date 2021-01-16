<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\ReadOnly;
use Kcs\Serializer\Annotation\SerializedName;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\Xml\Element;
use Kcs\Serializer\Annotation\Xml\XmlNamespace;
use Kcs\Serializer\Annotation\Xml\Root;

/**
 * @Root("publisher")
 * @XmlNamespace(uri="http://example.com/namespace2", prefix="ns2")
 * @ReadOnly()
 */
#[Root('publisher')]
#[XmlNamespace(uri: 'http://example.com/namespace2', prefix: 'ns2')]
#[ReadOnly()]
class Publisher
{
    /**
     * @Type("string")
     * @Element(namespace="http://example.com/namespace2")
     * @SerializedName("pub_name")
     */
    #[Type('string')]
    #[Element(namespace: 'http://example.com/namespace2')]
    #[SerializedName('pub_name')]
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
