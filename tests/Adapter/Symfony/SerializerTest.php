<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Adapter\Symfony;

use Kcs\Serializer\Adapter\Symfony\Serializer;
use Kcs\Serializer\DeserializationContext;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\SerializerBuilder;
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

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->serializer = $this->prophesize(SerializerInterface::class);
        $this->adapter = new Serializer($this->serializer->reveal());
    }

    public function testSerializeShouldCallSerialize(): void
    {
        $obj = new \stdClass();

        $this->serializer->serialize($obj, 'json', Argument::type(SerializationContext::class))
            ->shouldBeCalled()
            ->willReturn('{}')
        ;

        self::assertEquals('{}', $this->adapter->serialize($obj, 'json'));
    }

    public function testSerializeShouldForwardSerializationGroups(): void
    {
        $obj = new \stdClass();

        $this->serializer
            ->serialize($obj, 'json', Argument::that(static function (SerializationContext $context): bool {
                self::assertEquals(['group1', 'group2'], $context->attributes->get('groups'));

                return true;
            }))
            ->shouldBeCalled()
            ->willReturn('{}')
        ;

        $this->adapter->serialize($obj, 'json', ['groups' => ['group1', 'group2']]);
    }

    public function testDeserializeShouldCallDeserialize(): void
    {
        $obj = new \stdClass();

        $this->serializer
            ->deserialize(
                '{}',
                new Type('stdClass'),
                'json',
                Argument::type(DeserializationContext::class)
            )
            ->shouldBeCalled()
            ->willReturn($obj)
        ;

        self::assertEquals($obj, $this->adapter->deserialize('{}', \stdClass::class, 'json'));
    }

    public function testDeserializeShouldForwardDeserializationGroups(): void
    {
        $obj = new \stdClass();

        $this->serializer
            ->deserialize(
                '{}',
                new Type('stdClass'),
                'json',
                Argument::that(static function (DeserializationContext $context): bool {
                    self::assertEquals(['group1', 'group2'], $context->attributes->get('groups'));

                    return true;
                })
            )
            ->shouldBeCalled()
            ->willReturn($obj)
        ;

        self::assertEquals($obj, $this->adapter->deserialize(
            '{}',
            \stdClass::class,
            'json',
            ['groups' => ['group1', 'group2']]
        ));
    }

    public function testDeserializeShouldForwardTargetObject(): void
    {
        $obj = new GetSetObject();

        $this->serializer
            ->deserialize(
                '{}',
                new Type(GetSetObject::class),
                'json',
                Argument::that(static function (DeserializationContext $context) use ($obj): bool {
                    self::assertSame($obj, $context->attributes->get('target'));

                    return true;
                })
            )
            ->shouldBeCalled()
            ->willReturn($obj)
        ;

        self::assertEquals($obj, $this->adapter->deserialize(
            '{}',
            GetSetObject::class,
            'json',
            ['object_to_populate' => $obj]
        ));
    }

    public function testEncode()
    {
        $serializer = new Serializer(SerializerBuilder::create()->addDefaultSerializationVisitors()->build());
        $data = ['foo', [5, 3]];

        $result = $serializer->encode($data, 'json');
        $this->assertEquals(\json_encode($data), $result);
    }

    public function testDecode()
    {
        $serializer = new Serializer(SerializerBuilder::create()->addDefaultDeserializationVisitors()->build());
        $data = ['foo', [5, 3]];

        $result = $serializer->decode(\json_encode($data), 'json');
        $this->assertEquals($data, $result);
    }
}
