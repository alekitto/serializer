<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Serializer;

use Kcs\Serializer\Construction\UnserializeObjectConstructor;
use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Handler\DateHandler;
use Kcs\Serializer\Handler\HandlerRegistry;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\Serializer;
use Kcs\Serializer\Tests\Fixtures\InvalidUsageOfXmlValue;
use Kcs\Serializer\Tests\Fixtures\ObjectWithNamespacesAndList;
use Kcs\Serializer\Tests\Fixtures\ObjectWithVirtualXmlProperties;
use Kcs\Serializer\Tests\Fixtures\ObjectWithXmlKeyValuePairs;
use Kcs\Serializer\Tests\Fixtures\ObjectWithXmlNamespaces;
use Kcs\Serializer\Tests\Fixtures\ObjectWithXmlRootNamespace;
use Kcs\Serializer\Tests\Fixtures\Person;
use Kcs\Serializer\Tests\Fixtures\PersonCollection;
use Kcs\Serializer\Tests\Fixtures\PersonLocation;
use Kcs\Serializer\Tests\Fixtures\SimpleClassObject;
use Kcs\Serializer\Tests\Fixtures\SimpleSubClassObject;
use Kcs\Serializer\Type\Type;

class XmlSerializationTest extends BaseSerializationTest
{
    /**
     * @expectedException \Kcs\Serializer\Exception\RuntimeException
     */
    public function testInvalidUsageOfXmlValue()
    {
        $obj = new InvalidUsageOfXmlValue();
        $this->serialize($obj);
    }

    /**
     * @dataProvider getXMLBooleans
     */
    public function testXMLBooleans(string $xmlBoolean, bool $boolean)
    {
        if ($this->hasDeserializer()) {
            self::assertSame($boolean, $this->deserialize('<result>'.$xmlBoolean.'</result>', 'boolean'));
        }
    }

    public function getXMLBooleans(): iterable
    {
        return [
            ['true', true],
            ['false', false],
            ['1', true],
            ['0', false],
        ];
    }

    public function testPropertyIsObjectWithAttributeAndValue()
    {
        $personCollection = new PersonLocation();
        $person = new Person();
        $person->name = 'Matthias Noback';
        $person->age = 28;
        $personCollection->person = $person;
        $personCollection->location = 'The Netherlands';

        self::assertEquals($this->getContent('person_location'), $this->serialize($personCollection));
    }

    public function testPropertyIsCollectionOfObjectsWithAttributeAndValue()
    {
        $personCollection = new PersonCollection();
        $person = new Person();
        $person->name = 'Matthias Noback';
        $person->age = 28;
        $personCollection->persons->add($person);
        $personCollection->location = 'The Netherlands';

        self::assertEquals($this->getContent('person_collection'), $this->serialize($personCollection));
    }

    /**
     * @expectedException \Kcs\Serializer\Exception\InvalidArgumentException
     * @expectedExceptionMessage The document type "<!DOCTYPE author [<!ENTITY foo SYSTEM "php://filter/read=convert.base64-encode/resource=XmlSerializationTest.php">]>" is not allowed. If it is safe, you may add it to the whitelist configuration.
     */
    public function testExternalEntitiesAreDisabledByDefault()
    {
        $this->deserialize('<?xml version="1.0"?>
            <!DOCTYPE author [
                <!ENTITY foo SYSTEM "php://filter/read=convert.base64-encode/resource='.\basename(__FILE__).'">
            ]>
            <result>
                &foo;
            </result>', 'stdClass');
    }

    /**
     * @expectedException \Kcs\Serializer\Exception\InvalidArgumentException
     * @expectedExceptionMessage The document type "<!DOCTYPE foo>" is not allowed. If it is safe, you may add it to the whitelist configuration.
     */
    public function testDocumentTypesAreNotAllowed()
    {
        $this->deserialize('<?xml version="1.0"?><!DOCTYPE foo><foo></foo>', 'stdClass');
    }

    public function testWhitelistedDocumentTypesAreAllowed()
    {
        $this->deserializationVisitors['xml']->setDoctypeWhitelist([
            '<!DOCTYPE authorized SYSTEM "http://authorized_url.dtd">',
            '<!DOCTYPE author [<!ENTITY foo SYSTEM "php://filter/read=convert.base64-encode/resource='.\basename(__FILE__).'">]>', ]);

        $this->serializer->deserialize('<?xml version="1.0"?>
            <!DOCTYPE authorized SYSTEM "http://authorized_url.dtd">
            <foo></foo>', Type::from('stdClass'), 'xml');

        $this->serializer->deserialize('<?xml version="1.0"?>
            <!DOCTYPE author [
                <!ENTITY foo SYSTEM "php://filter/read=convert.base64-encode/resource='.\basename(__FILE__).'">
            ]>
            <foo></foo>', Type::from('stdClass'), 'xml');

        // Exception has not thrown
        self::assertTrue(true);
    }

    public function testVirtualAttributes()
    {
        self::assertEquals(
            $this->getContent('virtual_attributes'),
            $this->serialize(new ObjectWithVirtualXmlProperties(), SerializationContext::create()->setGroups(['attributes']))
        );
    }

    public function testVirtualValues()
    {
        self::assertEquals(
            $this->getContent('virtual_values'),
            $this->serialize(new ObjectWithVirtualXmlProperties(), SerializationContext::create()->setGroups(['values']))
        );
    }

    public function testVirtualXmlList()
    {
        self::assertEquals(
            $this->getContent('virtual_properties_list'),
            $this->serialize(new ObjectWithVirtualXmlProperties(), SerializationContext::create()->setGroups(['list']))
        );
    }

    public function testVirtualXmlMap()
    {
        self::assertEquals(
            $this->getContent('virtual_properties_map'),
            $this->serialize(new ObjectWithVirtualXmlProperties(), SerializationContext::create()->setGroups(['map']))
        );
    }

    public function testObjectWithNamespacesAndList()
    {
        $object = new ObjectWithNamespacesAndList();
        $object->name = 'name';
        $object->nameAlternativeB = 'nameB';

        $object->phones = ['111', '222'];
        $object->addresses = ['A' => 'Street 1', 'B' => 'Street 2'];

        $object->phonesAlternativeB = ['555', '666'];
        $object->addressesAlternativeB = ['A' => 'Street 5', 'B' => 'Street 6'];

        $object->phonesAlternativeC = ['777', '888'];
        $object->addressesAlternativeC = ['A' => 'Street 7', 'B' => 'Street 8'];

        $object->phonesAlternativeD = ['999', 'AAA'];
        $object->addressesAlternativeD = ['A' => 'Street 9', 'B' => 'Street A'];

        self::assertEquals(
            $this->getContent('object_with_namespaces_and_list'),
            $this->serialize($object, SerializationContext::create())
        );
        self::assertEquals(
            $object,
            $this->deserialize($this->getContent('object_with_namespaces_and_list'), \get_class($object))
        );
    }

    public function testArrayKeyValues()
    {
        self::assertEquals($this->getContent('array_key_values'), $this->serializer->serialize(new ObjectWithXmlKeyValuePairs(), 'xml'));
    }

    /**
     * @dataProvider getDateTime
     * @group datetime
     */
    public function testDateTimeNoCData($key, $value, $type)
    {
        $handlerRegistry = new HandlerRegistry();
        $handlerRegistry->registerSubscribingHandler(new DateHandler(\DateTime::ISO8601, 'UTC', false));
        $objectConstructor = new UnserializeObjectConstructor();

        $serializer = new Serializer($this->factory, $handlerRegistry, $objectConstructor, $this->serializationVisitors, $this->deserializationVisitors);

        self::assertEquals($this->getContent($key.'_no_cdata'), $serializer->serialize($value, $this->getFormat()));
    }

    public function testObjectWithXmlNamespaces()
    {
        $object = new ObjectWithXmlNamespaces('This is a nice title.', 'Foo Bar', new \DateTime('2011-07-30 00:00', new \DateTimeZone('UTC')), 'en');

        self::assertEquals($this->getContent('object_with_xml_namespaces'), $this->serialize($object));

        $xml = \simplexml_load_string($this->serialize($object));
        $xml->registerXPathNamespace('ns1', 'http://purl.org/dc/elements/1.1/');
        $xml->registerXPathNamespace('ns2', 'http://schemas.google.com/g/2005');
        $xml->registerXPathNamespace('ns3', 'http://www.w3.org/2005/Atom');

        self::assertEquals('2011-07-30T00:00:00+00:00', $this->xpathFirstToString($xml, './@created_at'));
        self::assertEquals('1edf9bf60a32d89afbb85b2be849e3ceed5f5b10', $this->xpathFirstToString($xml, './@ns2:etag'));
        self::assertEquals('en', $this->xpathFirstToString($xml, './@ns1:language'));
        self::assertEquals('This is a nice title.', $this->xpathFirstToString($xml, './ns1:title'));
        self::assertEquals('Foo Bar', $this->xpathFirstToString($xml, './ns3:author'));

        $deserialized = $this->deserialize($this->getContent('object_with_xml_namespacesalias'), \get_class($object));
        self::assertEquals('2011-07-30T00:00:00+0000', $this->getField($deserialized, 'createdAt')->format(\DateTime::ISO8601));
        self::assertAttributeEquals('This is a nice title.', 'title', $deserialized);
        self::assertAttributeSame('1edf9bf60a32d89afbb85b2be849e3ceed5f5b10', 'etag', $deserialized);
        self::assertAttributeSame('en', 'language', $deserialized);
        self::assertAttributeEquals('Foo Bar', 'author', $deserialized);
    }

    public function testObjectWithXmlRootNamespace()
    {
        $object = new ObjectWithXmlRootNamespace('This is a nice title.', 'Foo Bar', new \DateTime('2011-07-30 00:00', new \DateTimeZone('UTC')), 'en');
        self::assertEquals($this->getContent('object_with_xml_root_namespace'), $this->serialize($object));
    }

    public function testXmlNamespacesInheritance()
    {
        $object = new SimpleClassObject();
        $object->foo = 'foo';
        $object->bar = 'bar';
        $object->moo = 'moo';

        self::assertEquals($this->getContent('simple_class_object'), $this->serialize($object));

        $childObject = new SimpleSubClassObject();
        $childObject->foo = 'foo';
        $childObject->bar = 'bar';
        $childObject->moo = 'moo';
        $childObject->baz = 'baz';
        $childObject->qux = 'qux';

        self::assertEquals($this->getContent('simple_subclass_object'), $this->serialize($childObject));
    }

    private function xpathFirstToString(\SimpleXMLElement $xml, string $xpath): string
    {
        $nodes = $xml->xpath($xpath);

        return (string) \reset($nodes);
    }

    /**
     * {@inheritdoc}
     */
    protected function getContent(string $key): string
    {
        if (! \file_exists($file = __DIR__.'/xml/'.$key.'.xml')) {
            throw new InvalidArgumentException(\sprintf('The key "%s" is not supported.', $key));
        }

        return \file_get_contents($file);
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormat(): string
    {
        return 'xml';
    }
}
