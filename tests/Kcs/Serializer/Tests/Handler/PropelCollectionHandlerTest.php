<?php

namespace Kcs\Serializer\Tests\Handler;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\SerializerBuilder;

class PropelCollectionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  $serializer \Kcs\Serializer\Serializer */
    private $serializer;

    public function setUp()
    {
        $this->serializer = SerializerBuilder::create()
            ->addDefaultHandlers() //load PropelCollectionHandler
            ->build();
    }

    public function testSerializePropelObjectCollection()
    {
        $collection = new \PropelObjectCollection();
        $collection->setData([new TestSubject('lolo'), new TestSubject('pepe')]);
        $json = $this->serializer->serialize($collection, 'json');

        $data = json_decode($json, true);

        $this->assertCount(2, $data); //will fail if PropelCollectionHandler not loaded

        foreach ($data as $testSubject) {
            $this->assertArrayHasKey('name', $testSubject);
        }
    }
}

/**
 * @AccessType("property")
 */
class TestSubject
{
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
