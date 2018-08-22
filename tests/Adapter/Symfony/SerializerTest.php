<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Adapter\Symfony;

use Kcs\Serializer\Adapter\Symfony\Serializer;
use Kcs\Serializer\DeserializationContext;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\SerializerInterface;
use Kcs\Serializer\Tests\Fixtures\GetSetObject;
use Kcs\Serializer\Type\Type;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class SerializerTest extends TestCase
{
    /**
     * @var SerializerInterface|ObjectProphecy
     */
    private $serializer;

    /**
     * @var Serializer
     */
    private $adapter;

    protected function setUp()
    {
        $this->serializer = $this->prophesize(SerializerInterface::class);
        $this->adapter = new Serializer($this->serializer->reveal());
    }

    public function testSerializeShouldCallSerialize()
    {
        $obj = new \stdClass();

        $this->serializer->serialize($obj, 'json', Argument::type(SerializationContext::class))
            ->shouldBeCalled()
            ->willReturn('{}');

        $this->assertEquals('{}', $this->adapter->serialize($obj, 'json'));
    }

    public function testSerializeShouldForwardSerializationGroups()
    {
        $obj = new \stdClass();

        $this->serializer->serialize($obj, 'json', Argument::that(function (SerializationContext $context) {
            self::assertEquals(['group1', 'group2'], $context->attributes->get('groups'));

            return true;
        }))
            ->shouldBeCalled()
            ->willReturn('{}');

        $this->adapter->serialize($obj, 'json', ['groups' => ['group1', 'group2']]);
    }

    public function testDeserializeShouldCallDeserialize()
    {
        $obj = new \stdClass();

        $this->serializer->deserialize('{}', new Type('stdClass'), 'json', Argument::type(DeserializationContext::class))
            ->shouldBeCalled()
            ->willReturn($obj);

        $this->assertEquals($obj, $this->adapter->deserialize('{}', \stdClass::class, 'json'));
    }

    public function testDeserializeShouldForwardDeserializationGroups()
    {
        $obj = new \stdClass();

        $this->serializer->deserialize('{}', new Type('stdClass'), 'json', Argument::that(function (DeserializationContext $context) {
            self::assertEquals(['group1', 'group2'], $context->attributes->get('groups'));

            return true;
        }))
            ->shouldBeCalled()
            ->willReturn($obj);

        $this->assertEquals($obj, $this->adapter->deserialize('{}', \stdClass::class, 'json', ['groups' => ['group1', 'group2']]));
    }

    public function testDeserializeShouldForwardTargetObject()
    {
        $obj = new GetSetObject();

        $this->serializer->deserialize('{}', new Type(GetSetObject::class), 'json', Argument::that(function (DeserializationContext $context) use ($obj) {
            self::assertSame($obj, $context->attributes->get('target'));

            return true;
        }))
            ->shouldBeCalled()
            ->willReturn($obj);

        $this->assertEquals($obj, $this->adapter->deserialize('{}', GetSetObject::class, 'json', ['object_to_populate' => $obj]));
    }
}
