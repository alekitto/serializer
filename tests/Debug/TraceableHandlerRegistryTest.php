<?php

declare(strict_types=1);

namespace Kcs\Serializer\Tests\Debug;

use Kcs\Serializer\Debug\TraceableHandlerRegistry;
use Kcs\Serializer\Direction;
use Kcs\Serializer\Handler\DeserializationHandlerInterface;
use Kcs\Serializer\Handler\HandlerRegistryInterface;
use Kcs\Serializer\Handler\SerializationHandlerInterface;
use Kcs\Serializer\Handler\SubscribingHandlerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class TraceableHandlerRegistryTest extends TestCase
{
    use ProphecyTrait;

    /** @var HandlerRegistryInterface|ObjectProphecy */
    private ObjectProphecy $decorated;
    private TraceableHandlerRegistry $handlerRegistry;

    protected function setUp(): void
    {
        $this->decorated = $this->prophesize(HandlerRegistryInterface::class);
        $this->handlerRegistry = new TraceableHandlerRegistry($this->decorated->reveal());
    }

    public function testRegisterSubscribingHandlerShouldPassCallToDecorated(): void
    {
        $handler = $this->prophesize(SubscribingHandlerInterface::class);
        $this->decorated->registerSubscribingHandler($handler)
            ->shouldBeCalled()
            ->willReturn($this->decorated);

        $this->handlerRegistry->registerSubscribingHandler($handler->reveal());
    }

    public function testRegisterHandlerShouldPassCallToDecorated(): void
    {
        $handler = static function () {
        };
        $this->decorated->registerHandler(Direction::DIRECTION_SERIALIZATION, 'type', $handler)
            ->shouldBeCalled()
            ->willReturn($this->decorated);

        $this->handlerRegistry->registerHandler(Direction::DIRECTION_SERIALIZATION, 'type', $handler);
    }

    public function testRegisterSerializationHandlerShouldPassCallToDecorated(): void
    {
        $handler = $this->prophesize(SerializationHandlerInterface::class);
        $this->decorated->registerSerializationHandler($handler)
            ->shouldBeCalled()
            ->willReturn($this->decorated);

        $this->handlerRegistry->registerSerializationHandler($handler->reveal());
    }

    public function testRegisterDeserializationHandlerShouldPassCallToDecorated(): void
    {
        $handler = $this->prophesize(DeserializationHandlerInterface::class);
        $this->decorated->registerDeserializationHandler($handler)
            ->shouldBeCalled()
            ->willReturn($this->decorated);

        $this->handlerRegistry->registerDeserializationHandler($handler->reveal());
    }

    public function testGetHandlerShouldReturnADecoratedHandler(): void
    {
        $decorated = static function () {
        };
        $this->decorated->getHandler(Direction::DIRECTION_SERIALIZATION, 'type')
            ->shouldBeCalled()
            ->willReturn($decorated);

        $handler = $this->handlerRegistry->getHandler(Direction::DIRECTION_SERIALIZATION, 'type');

        self::assertNotSame($decorated, $handler);
        self::assertIsCallable($handler);

        $handler();
        self::assertCount(1, $this->handlerRegistry->calls);
    }
}
