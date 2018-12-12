<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Handler;

use Cake\Chronos\Chronos;
use Kcs\Serializer\Handler\DateHandler;
use Kcs\Serializer\Handler\SubscribingHandlerInterface;
use Kcs\Serializer\Type\Type;

class DateHandlerTest extends AbstractHandlerTest
{
    public function testGetSubscribingMethodsShouldReturnAllTypes()
    {
        $this->assertCount(9, iterator_to_array($this->handler->getSubscribingMethods()));
    }

    public function testSerializeDateTimeShouldUseFormatTheDateTimeObject()
    {
        $type = Type::parse(\DateTime::class."<'d/m/Y H:i:s'>");

        $this->visitor->visitString('12/12/2018 22:00:00', $type, $this->context)->shouldBeCalled();
        $this->handler->serializeDateTime($this->visitor->reveal(), new \DateTime('2018-12-12T22:00:00Z'), $type, $this->context->reveal());
    }

    public function testSerializeDateTimeShouldUseDefaultFormat()
    {
        $type = Type::parse(\DateTime::class);

        $this->visitor->visitString('2018-12-12T22:00:00+00:00', $type, $this->context)->shouldBeCalled();
        $this->handler->serializeDateTime($this->visitor->reveal(), new \DateTime('2018-12-12T22:00:00Z'), $type, $this->context->reveal());
    }

    public function testSerializeDateTimeShouldReturnAnIntegerIfFormatIsTimestamp()
    {
        $type = Type::parse(\DateTime::class."<'U'>");

        $this->visitor->visitInteger(1544652000, $type, $this->context)->shouldBeCalled();
        $this->handler->serializeDateTime($this->visitor->reveal(), new \DateTime('2018-12-12T22:00:00Z'), $type, $this->context->reveal());
    }

    public function testSerializeDateTimeShouldFormatTheDateInterval()
    {
        $type = Type::parse(\DateInterval::class);

        $this->visitor->visitString('P7D', $type, $this->context)->shouldBeCalled();
        $this->handler->serializeDateInterval($this->visitor->reveal(), new \DateInterval('P1W'), $type, $this->context->reveal());
    }

    public function testSerializeDateTimeShouldHandleDateTimeImmutable()
    {
        $type = Type::parse(\DateTimeImmutable::class);

        $this->visitor->visitString('2018-12-12T22:00:00+00:00', $type, $this->context)->shouldBeCalled();
        $this->handler->serializeDateTime($this->visitor->reveal(), new \DateTimeImmutable('2018-12-12T22:00:00Z'), $type, $this->context->reveal());
    }

    public function testDeserializeDateTime()
    {
        $this->assertNull($this->handler->deserializeDateTime($this->visitor->reveal(), null, Type::parse(\DateTime::class), $this->context->reveal()));

        $this->assertEquals(
            new \DateTimeImmutable('2018-12-12T22:00:00Z'),
            $this->handler->deserializeDateTimeImmutable($this->visitor->reveal(), '2018-12-12T22:00:00+00:00', Type::parse(\DateTimeImmutable::class), $this->context->reveal())
        );
        $this->assertEquals(
            new \DateTime('2018-12-12T22:00:00Z'),
            $this->handler->deserializeDateTime($this->visitor->reveal(), '2018-12-12T22:00:00+00:00', Type::parse(\DateTime::class), $this->context->reveal())
        );
        $this->assertEquals(
            new Chronos('2018-12-12T22:00:00Z'),
            $this->handler->deserializeChronos($this->visitor->reveal(), '2018-12-12T22:00:00+00:00', Type::parse(\DateTime::class), $this->context->reveal())
        );
    }

    protected function createHandler(): SubscribingHandlerInterface
    {
        return new DateHandler();
    }
}
