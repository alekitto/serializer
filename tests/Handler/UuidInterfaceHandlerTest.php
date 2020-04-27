<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Handler;

use Kcs\Serializer\Handler\SubscribingHandlerInterface;
use Kcs\Serializer\Handler\UuidInterfaceHandler;
use Kcs\Serializer\Type\Type;
use Prophecy\Argument;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UuidInterfaceHandlerTest extends AbstractHandlerTest
{
    public function testGetSubscribingMethodsShouldReturnAllTypes(): void
    {
        self::assertCount(18, \iterator_to_array($this->handler::getSubscribingMethods()));
    }

    public function testSerializeShouldHandleNullValue(): void
    {
        $this->visitor->visitNull(null, Type::null(), $this->context)->shouldBeCalled();
        $this->handler->serialize($this->visitor->reveal(), null, Type::parse(UuidInterface::class), $this->context->reveal());
    }

    public function testSerializeShouldReturnStringRepresentation(): void
    {
        $this->visitor->visitString('b9fe1e68-667c-4bd3-b8ce-c6d3c0640b95', Argument::type(Type::class), $this->context)->shouldBeCalled();

        $uuid = Uuid::fromString('b9fe1e68-667c-4bd3-b8ce-c6d3c0640b95');
        $this->handler->serialize($this->visitor->reveal(), $uuid, Type::parse(Uuid::class), $this->context->reveal());
    }

    public function testDeserializeShouldHandleNullValue(): void
    {
        $this->visitor->visitNull(null, Type::null(), $this->context)->shouldBeCalled();
        $this->handler->deserialize($this->visitor->reveal(), null, Type::parse(UuidInterface::class), $this->context->reveal());
    }

    public function testDeserializeShouldReturnUuidObject(): void
    {
        $uuid = Uuid::fromString('b9fe1e68-667c-4bd3-b8ce-c6d3c0640b95');

        self::assertEquals(
            $uuid,
            $this->handler->deserialize($this->visitor->reveal(), 'b9fe1e68-667c-4bd3-b8ce-c6d3c0640b95', Type::parse(UuidInterface::class), $this->context->reveal())
        );
    }

    protected function createHandler(): SubscribingHandlerInterface
    {
        return new UuidInterfaceHandler();
    }
}
