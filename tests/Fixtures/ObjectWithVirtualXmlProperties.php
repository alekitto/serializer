<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\Groups;
use Kcs\Serializer\Attribute\SerializedName;
use Kcs\Serializer\Attribute\Since;
use Kcs\Serializer\Attribute\Until;
use Kcs\Serializer\Attribute\VirtualProperty;
use Kcs\Serializer\Attribute\Xml;

class ObjectWithVirtualXmlProperties
{
    #[VirtualProperty]
    #[SerializedName("foo")]
    #[Groups(['attributes'])]
    #[Xml\Attribute]
    public function getVirualXmlAttributeValue()
    {
        return 'bar';
    }

    #[VirtualProperty]
    #[SerializedName("xml-value")]
    #[Groups(['values'])]
    #[Xml\Value]
    public function getVirualXmlValue()
    {
        return 'xml-value';
    }

    #[VirtualProperty]
    #[SerializedName("list")]
    #[Groups(['list'])]
    #[Xml\XmlList(entry: 'val', inline: true)]
    public function getVirualXmlList()
    {
        return ['One', 'Two'];
    }

    #[VirtualProperty]
    #[SerializedName("map")]
    #[Groups(['map'])]
    #[Xml\Map(keyAttribute: 'key')]
    public function getVirualXmlMap()
    {
        return [
            'key-one' => 'One',
            'key-two' => 'Two',
        ];
    }

    #[VirtualProperty]
    #[SerializedName("low")]
    #[Groups(['versions'])]
    #[Until('8')]
    public function getVirualLowValue()
    {
        return 1;
    }

    #[VirtualProperty]
    #[SerializedName("high")]
    #[Groups(['versions'])]
    #[Since('8')]
    public function getVirualHighValue()
    {
        return 8;
    }
}
