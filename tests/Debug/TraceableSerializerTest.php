<?php

declare(strict_types=1);

namespace Kcs\Serializer\Tests\Debug;

use Kcs\Serializer\Debug\TraceableSerializer;
use Kcs\Serializer\SerializerInterface;
use Kcs\Serializer\Type\Type;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use stdClass;
use Symfony\Component\VarDumper\Cloner\VarCloner;

class TraceableSerializerTest extends TestCase
{
    use ProphecyTrait;

    /** @var SerializerInterface|ObjectProphecy */
    private ObjectProphecy $serializer;
    private TraceableSerializer $traceableSerializer;
    private VarCloner $cloner;

    protected function setUp(): void
    {
        $this->serializer = $this->prophesize(SerializerInterface::class);
        $this->traceableSerializer = new TraceableSerializer($this->serializer->reveal());
        $this->cloner = new VarCloner();
    }

    public function testSerializeShouldTrackSerializeCall(): void
    {
        $obj = new stdClass();
        $this->serializer->serialize($obj, 'json', null, null)
            ->shouldBeCalled()
            ->willReturn('{}');

        $this->traceableSerializer->serialize($obj, 'json');

        self::assertCount(1, $this->traceableSerializer->serializeOperations);
        self::assertEquals('json', $this->traceableSerializer->serializeOperations[0]['format']);
        self::assertEquals($this->cloner->cloneVar(null), $this->traceableSerializer->serializeOperations[0]['type']);
        self::assertEquals('{}', $this->traceableSerializer->serializeOperations[0]['result']);
        self::assertNull($this->traceableSerializer->serializeOperations[0]['exception']);
    }

    public function testDeserializeShouldTrackDeserializeCall(): void
    {
        $type = Type::parse('stdClass');
        $this->serializer->deserialize('{}', $type, 'json', null)
            ->shouldBeCalled()
            ->willReturn(new stdClass());

        $this->traceableSerializer->deserialize('{}', $type, 'json');

        self::assertCount(1, $this->traceableSerializer->deserializeOperations);
        self::assertEquals('json', $this->traceableSerializer->deserializeOperations[0]['format']);
        self::assertEquals(
            $this->cloner->cloneVar(['name' => 'stdClass', 'params' => []]),
            $this->traceableSerializer->deserializeOperations[0]['type']
        );
        self::assertNull($this->traceableSerializer->deserializeOperations[0]['exception']);
    }

    public function testNormalizeShouldTrackSerializeCall(): void
    {
        $obj = new stdClass();
        $this->serializer->normalize($obj, null)
            ->shouldBeCalled()
            ->willReturn([]);

        $this->traceableSerializer->normalize($obj);

        self::assertCount(1, $this->traceableSerializer->serializeOperations);
        self::assertEquals('array', $this->traceableSerializer->serializeOperations[0]['format']);
        self::assertEquals($this->cloner->cloneVar(null), $this->traceableSerializer->serializeOperations[0]['type']);
        self::assertEquals($this->cloner->cloneVar([]), $this->traceableSerializer->serializeOperations[0]['result']);
        self::assertNull($this->traceableSerializer->serializeOperations[0]['exception']);
    }

    public function testDenormalizeShouldTrackDeserializeCall(): void
    {
        $type = Type::parse('stdClass');
        $this->serializer->denormalize([], $type, null)
            ->shouldBeCalled()
            ->willReturn(new stdClass());

        $this->traceableSerializer->denormalize([], $type);

        self::assertCount(1, $this->traceableSerializer->deserializeOperations);
        self::assertEquals('array', $this->traceableSerializer->deserializeOperations[0]['format']);
        self::assertEquals(
            $this->cloner->cloneVar(['name' => 'stdClass', 'params' => []]),
            $this->traceableSerializer->deserializeOperations[0]['type']
        );
        self::assertNull($this->traceableSerializer->deserializeOperations[0]['exception']);
    }

    public function testSerializeShouldTrackFailedCalls(): void
    {
        $obj = new stdClass();
        $this->serializer->serialize($obj, 'json', null, null)
            ->shouldBeCalled()
            ->willThrow(new Exception());

        try {
            $this->traceableSerializer->serialize($obj, 'json');
            self::fail('Exception expected');
        } catch (Exception $e) {
        }

        self::assertCount(1, $this->traceableSerializer->serializeOperations);
        self::assertEquals('json', $this->traceableSerializer->serializeOperations[0]['format']);
        self::assertEquals($this->cloner->cloneVar(null), $this->traceableSerializer->serializeOperations[0]['type']);
        self::assertNull($this->traceableSerializer->serializeOperations[0]['result']);
        self::assertNotNull($this->traceableSerializer->serializeOperations[0]['exception']);
    }
}

class Exception extends \Exception
{
}
