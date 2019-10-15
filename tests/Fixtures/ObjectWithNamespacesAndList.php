<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\SerializedName;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\Xml;

/**
 * @Xml\Root("ObjectWithNamespacesAndList", namespace="http://example.com/namespace")
 * @Xml\XmlNamespace(uri="http://example.com/namespace")
 * @Xml\XmlNamespace(uri="http://example.com/namespace2", prefix="x")
 * @AccessType("property")
 */
class ObjectWithNamespacesAndList
{
    /**
     * @Type("string")
     * @SerializedName("name")
     * @Xml\Element(namespace="http://example.com/namespace")
     */
    public $name;
    /**
     * @Type("string")
     * @SerializedName("name")
     * @Xml\Element(namespace="http://example.com/namespace2")
     */
    public $nameAlternativeB;

    /**
     * @Type("array<string>")
     * @SerializedName("phones")
     * @Xml\Element(namespace="http://example.com/namespace2")
     * @Xml\XmlList(inline = false, entry = "phone", namespace="http://example.com/namespace2")
     */
    public $phones;
    /**
     * @Type("array<string,string>")
     * @SerializedName("addresses")
     * @Xml\Element(namespace="http://example.com/namespace2")
     * @Xml\Map(inline = false, entry = "address", keyAttribute = "id", namespace="http://example.com/namespace2")
     */
    public $addresses;

    /**
     * @Type("array<string>")
     * @SerializedName("phones")
     * @Xml\XmlList(inline = true, entry = "phone", namespace="http://example.com/namespace")
     */
    public $phonesAlternativeB;
    /**
     * @Type("array<string,string>")
     * @SerializedName("addresses")
     * @Xml\Map(inline = true, entry = "address", keyAttribute = "id", namespace="http://example.com/namespace")
     */
    public $addressesAlternativeB;

    /**
     * @Type("array<string>")
     * @SerializedName("phones")
     * @Xml\XmlList(inline = true, entry = "phone",  namespace="http://example.com/namespace2")
     */
    public $phonesAlternativeC;
    /**
     * @Type("array<string,string>")
     * @SerializedName("addresses")
     * @Xml\Map(inline = true, entry = "address", keyAttribute = "id", namespace="http://example.com/namespace2")
     */
    public $addressesAlternativeC;

    /**
     * @Type("array<string>")
     * @SerializedName("phones")
     * @Xml\XmlList(inline = false, entry = "phone")
     */
    public $phonesAlternativeD;
    /**
     * @Type("array<string,string>")
     * @SerializedName("addresses")
     * @Xml\Map(inline = false, entry = "address", keyAttribute = "id")
     */
    public $addressesAlternativeD;
}
