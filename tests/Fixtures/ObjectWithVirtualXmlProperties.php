<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\Groups;
use Kcs\Serializer\Annotation\SerializedName;
use Kcs\Serializer\Annotation\Since;
use Kcs\Serializer\Annotation\Until;
use Kcs\Serializer\Annotation\VirtualProperty;
use Kcs\Serializer\Annotation\Xml;

class ObjectWithVirtualXmlProperties
{
    /**
     * @VirtualProperty
     * @SerializedName("foo")
     * @Groups({"attributes"})
     * @Xml\Attribute
     */
    public function getVirualXmlAttributeValue()
    {
        return 'bar';
    }

    /**
     * @VirtualProperty
     * @SerializedName("xml-value")
     * @Groups({"values"})
     * @Xml\Value
     */
    public function getVirualXmlValue()
    {
        return 'xml-value';
    }

    /**
     * @VirtualProperty
     * @SerializedName("list")
     * @Groups({"list"})
     * @Xml\XmlList(inline = true, entry = "val")
     */
    public function getVirualXmlList()
    {
        return ['One', 'Two'];
    }

    /**
     * @VirtualProperty
     * @SerializedName("map")
     * @Groups({"map"})
     * @Xml\Map(keyAttribute = "key")
     */
    public function getVirualXmlMap()
    {
        return [
            'key-one' => 'One',
            'key-two' => 'Two',
        ];
    }

    /**
     * @VirtualProperty
     * @SerializedName("low")
     * @Groups({"versions"})
     * @Until("8")
     */
    public function getVirualLowValue()
    {
        return 1;
    }

    /**
     * @VirtualProperty
     * @SerializedName("hight")
     * @Groups({"versions"})
     * @Since("8")
     */
    public function getVirualHighValue()
    {
        return 8;
    }
}
