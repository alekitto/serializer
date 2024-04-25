<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\Immutable;
use Kcs\Serializer\Attribute\SerializedName;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Attribute\Xml\Element;
use Kcs\Serializer\Attribute\Xml\XmlNamespace;
use Kcs\Serializer\Attribute\Xml\Root;

#[Root('publisher')]
#[XmlNamespace(uri: 'http://example.com/namespace2', prefix: 'ns2')]
#[Immutable]
class Publisher
{
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
