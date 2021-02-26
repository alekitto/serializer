<?php

declare(strict_types=1);

namespace Kcs\Serializer\Tests\Bundle\DataCollector;

use Exception;
use Kcs\Serializer\Bundle\DataCollector\SerializerDataCollector;
use Kcs\Serializer\Debug\TraceableHandlerRegistry;
use Kcs\Serializer\Debug\TraceableSerializer;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\Cloner\VarCloner;

class SerializerDataCollectorTest extends TestCase
{
    use ProphecyTrait;

    /** @var TraceableSerializer|ObjectProphecy */
    private ObjectProphecy $serializer;

    /** @var TraceableHandlerRegistry|ObjectProphecy */
    private ObjectProphecy $handlerRegistry;
    private SerializerDataCollector $collector;
    private VarCloner $cloner;

    protected function setUp(): void
    {
        $this->serializer = $this->prophesize(TraceableSerializer::class);
        $this->handlerRegistry = $this->prophesize(TraceableHandlerRegistry::class);
        $this->cloner = new VarCloner();
        $this->collector = new SerializerDataCollector($this->serializer->reveal(), $this->handlerRegistry->reveal());
    }

    public function testInitialIsEmpty(): void
    {
        self::assertTrue($this->collector->isEmpty());
        self::assertEquals(0, $this->collector->getCount());
        self::assertEquals(0, $this->collector->getErrorCount());
        self::assertEquals('kcs_serializer', $this->collector->getName());
    }

    public function testCollectShouldCollectInfosFromTraceableObjects(): void
    {
        $this->serializer->serializeOperations = [
            [
                'data' => $this->cloner->cloneVar([]),
                'format' => 'json',
                'type' => $this->cloner->cloneVar(null),
                'context' => $this->cloner->cloneVar(null),
                'result' => null,
                'exception' => null,
            ],
        ];

        $this->serializer->deserializeOperations = [
            [
                'data' => $this->cloner->cloneVar([]),
                'format' => 'json',
                'type' => $this->cloner->cloneVar(null),
                'context' => $this->cloner->cloneVar(null),
                'result' => null,
                'exception' => null,
            ],
        ];

        $this->handlerRegistry->calls = [
            [
                'type' => 'custom',
                'direction' => 'SERIALIZE',
                'handler' => self::class . '::testMethod',
                'exception' => null,
            ],
        ];

        $this->collector->collect(new Request(), new Response());

        self::assertEquals(2, $this->collector->getCount());
        self::assertEquals(0, $this->collector->getErrorCount());
        self::assertCount(1, $this->collector->getSerializations());
        self::assertCount(1, $this->collector->getDeserializations());
        self::assertCount(1, $this->collector->getHandlerCalls());
        self::assertFalse($this->collector->isEmpty());
    }

    public function testShouldCountErrorsCorrectly(): void
    {
        $this->serializer->serializeOperations = [
            [
                'data' => $this->cloner->cloneVar([]),
                'format' => 'json',
                'type' => $this->cloner->cloneVar(null),
                'context' => $this->cloner->cloneVar(null),
                'result' => null,
                'exception' => null,
            ],
        ];

        $this->serializer->deserializeOperations = [
            [
                'data' => $this->cloner->cloneVar([]),
                'format' => 'json',
                'type' => $this->cloner->cloneVar(null),
                'context' => $this->cloner->cloneVar(null),
                'result' => null,
                'exception' => new Exception(),
            ],
        ];

        $this->collector->collect(new Request(), new Response());

        self::assertEquals(2, $this->collector->getCount());
        self::assertEquals(1, $this->collector->getErrorCount());
        self::assertFalse($this->collector->isEmpty());
    }
}
