<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Handler;

use Cake\Chronos\Chronos;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use Kcs\Serializer\Handler\DateHandler;
use Kcs\Serializer\Handler\SubscribingHandlerInterface;
use Kcs\Serializer\Type\Type;
use Symfony\Component\Clock\DatePoint;

use function iterator_to_array;

class DateHandlerTest extends AbstractHandlerTest
{
    public function testGetSubscribingMethodsShouldReturnAllTypes(): void
    {
        self::assertCount(14, iterator_to_array($this->handler::getSubscribingMethods()));
    }

    public function testSerializeDateTimeShouldUseFormatTheDateTimeObject(): void
    {
        $type = Type::parse(DateTime::class."<'d/m/Y H:i:s'>");

        $this->visitor->visitString('12/12/2018 22:00:00', $type, $this->context)->willReturn()->shouldBeCalled();
        $this->handler->serializeDateTime($this->visitor->reveal(), new DateTime('2018-12-12T22:00:00Z'), $type, $this->context->reveal());
    }

    public function testSerializeSafeDateTimeShouldUseFormatTheDateTimeObject(): void
    {
        $type = Type::parse(DateTime::class."<'d/m/Y H:i:s'>");

        $this->visitor->visitString('12/12/2018 22:00:00', $type, $this->context)->willReturn()->shouldBeCalled();
        $this->handler->serializeDateTime($this->visitor->reveal(), new \Safe\DateTime('2018-12-12T22:00:00Z'), $type, $this->context->reveal());
    }

    public function testSerializeDateTimeShouldUseDefaultFormat(): void
    {
        $type = Type::parse(DateTime::class);

        $this->visitor->visitString('2018-12-12T22:00:00+00:00', $type, $this->context)->willReturn()->shouldBeCalled();
        $this->handler->serializeDateTime($this->visitor->reveal(), new DateTime('2018-12-12T22:00:00Z'), $type, $this->context->reveal());
    }

    public function testSerializeDateTimeShouldReturnAnIntegerIfFormatIsTimestamp(): void
    {
        $type = Type::parse(DateTime::class."<'U'>");

        $this->visitor->visitInteger(1544652000, $type, $this->context)->willReturn()->shouldBeCalled();
        $this->handler->serializeDateTime($this->visitor->reveal(), new DateTime('2018-12-12T22:00:00Z'), $type, $this->context->reveal());
    }

    public function testSerializeDateTimeShouldFormatTheDateInterval(): void
    {
        $type = Type::parse(DateInterval::class);

        $this->visitor->visitString('P7D', $type, $this->context)->willReturn()->shouldBeCalled();
        $this->handler->serializeDateInterval($this->visitor->reveal(), new DateInterval('P1W'), $type, $this->context->reveal());
    }

    public function testSerializeDateTimeShouldHandleDateTimeImmutable(): void
    {
        $type = Type::parse(DateTimeImmutable::class);

        $this->visitor->visitString('2018-12-12T22:00:00+00:00', $type, $this->context)->willReturn()->shouldBeCalled();
        $this->handler->serializeDateTime($this->visitor->reveal(), new DateTimeImmutable('2018-12-12T22:00:00Z'), $type, $this->context->reveal());
    }

    public function testSerializeDateTimeShouldHandleSafeDateTimeImmutable(): void
    {
        $type = Type::parse(DateTimeImmutable::class);

        $this->visitor->visitString('2018-12-12T22:00:00+00:00', $type, $this->context)->willReturn()->shouldBeCalled();
        $this->handler->serializeDateTime($this->visitor->reveal(), new \Safe\DateTimeImmutable('2018-12-12T22:00:00Z'), $type, $this->context->reveal());
    }

    public function testSerializeDateTimeShouldHandleSymfonyDatePoint(): void
    {
        $type = Type::parse(DatePoint::class);

        $this->visitor->visitString('2018-12-12T22:00:00+00:00', $type, $this->context)->willReturn()->shouldBeCalled();
        $this->handler->serializeDateTime($this->visitor->reveal(), new DatePoint('2018-12-12T22:00:00Z'), $type, $this->context->reveal());
    }

    public function testDeserializeDateTime(): void
    {
        self::assertNull($this->handler->deserializeDateTime($this->visitor->reveal(), null, Type::parse(DateTime::class)));

        self::assertEquals(
            new DateTimeImmutable('2018-12-12T22:00:00Z'),
            $this->handler->deserializeDateTimeImmutable($this->visitor->reveal(), '2018-12-12T22:00:00+00:00', Type::parse(DateTimeImmutable::class))
        );
        self::assertEquals(
            new DateTime('2018-12-12T22:00:00Z'),
            $this->handler->deserializeDateTime($this->visitor->reveal(), '2018-12-12T22:00:00+00:00', Type::parse(DateTime::class))
        );
        self::assertEquals(
            new Chronos('2018-12-12T22:00:00Z'),
            $this->handler->deserializeChronos($this->visitor->reveal(), '2018-12-12T22:00:00+00:00', Type::parse(DateTime::class))
        );
        self::assertEquals(
            new DatePoint('2018-12-12T22:00:00Z'),
            $this->handler->deserializeDatePoint($this->visitor->reveal(), '2018-12-12T22:00:00+00:00', Type::parse(DateTimeImmutable::class))
        );
    }

    public function testDeserializeDateInterval(): void
    {
        $interval = $this->handler->deserializeDateInterval($this->visitor->reveal(), 'P1W');
        self::assertEquals(new DateInterval('P7D'), $interval);

        $interval = $this->handler->deserializeDateInterval($this->visitor->reveal(), '-P1Y');
        $expected = new DateInterval('P1Y');
        $expected->invert = 1;
        self::assertEquals($expected, $interval);
    }

    protected function createHandler(): SubscribingHandlerInterface
    {
        return new DateHandler();
    }
}
