<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\SerializedName;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\Xml;
use Kcs\Serializer\Metadata\Access;


#[Xml\Root("ObjectWithNamespacesAndList", namespace: "http://example.com/namespace")]
#[Xml\XmlNamespace(uri: "http://example.com/namespace")]
#[Xml\XmlNamespace(uri: "http://example.com/namespace2", prefix: "x")]
#[AccessType(Access\Type::Property)]
class ObjectWithNamespacesAndList
{
    #[Type("string")]
    #[SerializedName("name")]
    #[Xml\Element(namespace: "http://example.com/namespace")]
    public $name;

    #[Type("string")]
    #[SerializedName("name")]
    #[Xml\Element(namespace: "http://example.com/namespace2")]
    public $nameAlternativeB;

    #[Type("array<string>")]
    #[SerializedName("phones")]
    #[Xml\Element(namespace: "http://example.com/namespace2")]
    #[Xml\XmlList(entry: "phone", inline: false, namespace: "http://example.com/namespace2")]
    public $phones;

    #[Type("array<string, string>")]
    #[SerializedName("addresses")]
    #[Xml\Element(namespace: "http://example.com/namespace2")]
    #[Xml\Map(entry: "address", inline: false, namespace: "http://example.com/namespace2", keyAttribute: 'id')]
    public $addresses;

    #[Type("array<string>")]
    #[SerializedName("phones")]
    #[Xml\XmlList(entry: "phone", inline: true, namespace: "http://example.com/namespace")]
    public $phonesAlternativeB;

    #[Type("array<string, string>")]
    #[SerializedName("addresses")]
    #[Xml\Map(entry: "address", inline: true, namespace: "http://example.com/namespace", keyAttribute: 'id')]
    public $addressesAlternativeB;

    #[Type("array<string>")]
    #[SerializedName("phones")]
    #[Xml\XmlList(entry: "phone", inline: true, namespace: "http://example.com/namespace2")]
    public $phonesAlternativeC;

    #[Type("array<string, string>")]
    #[SerializedName("addresses")]
    #[Xml\Map(entry: "address", inline: true, namespace: "http://example.com/namespace2", keyAttribute: 'id')]
    public $addressesAlternativeC;

    #[Type("array<string>")]
    #[SerializedName("phones")]
    #[Xml\XmlList(entry: "phone", inline: false)]
    public $phonesAlternativeD;

    #[Type("array<string, string>")]
    #[SerializedName("addresses")]
    #[Xml\Map(entry: "address", inline: false, keyAttribute: 'id')]
    public $addressesAlternativeD;
}
