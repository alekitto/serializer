<?php

declare(strict_types=1);

namespace Kcs\Serializer\Handler;

use Cake\Chronos\Chronos;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Kcs\Serializer\Context;
use Kcs\Serializer\Direction;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;
use Kcs\Serializer\XmlSerializationVisitor;

use function assert;
use function is_string;
use function Safe\preg_replace;
use function sprintf;
use function str_ends_with;
use function str_replace;
use function substr;

class DateHandler implements SubscribingHandlerInterface
{
    private const DATEINTERVAL_FORMAT = '%RP%yY%mM%dDT%hH%iM%sS';

    private DateTimeZone $defaultTimezone;

    /**
     * {@inheritDoc}
     */
    public static function getSubscribingMethods(): iterable
    {
        yield [
            'type' => DateTime::class,
            'direction' => Direction::Deserialization,
            'method' => 'deserializeDateTime',
        ];

        foreach ([DateTimeImmutable::class, DateTimeInterface::class] as $class) {
            yield [
                'type' => $class,
                'direction' => Direction::Deserialization,
                'method' => 'deserializeDateTimeImmutable',
            ];
        }

        foreach ([DateTime::class, DateTimeImmutable::class, DateTimeInterface::class, \Safe\DateTime::class, \Safe\DateTimeImmutable::class] as $class) {
            yield [
                'type' => $class,
                'direction' => Direction::Serialization,
                'method' => 'serializeDateTime',
            ];
        }

        yield [
            'type' => DateInterval::class,
            'direction' => Direction::Serialization,
            'method' => 'serializeDateInterval',
        ];

        yield [
            'type' => DateInterval::class,
            'direction' => Direction::Deserialization,
            'method' => 'deserializeDateInterval',
        ];

        yield [
            'type' => Chronos::class,
            'direction' => Direction::Serialization,
            'method' => 'serializeDateTime',
        ];

        yield [
            'type' => Chronos::class,
            'direction' => Direction::Deserialization,
            'method' => 'deserializeChronos',
        ];
    }

    public function __construct(
        private readonly string $defaultFormat = DateTimeInterface::ATOM,
        string $defaultTimezone = 'UTC',
        private readonly bool $xmlCData = true,
    ) {
        $this->defaultTimezone = new DateTimeZone($defaultTimezone);
    }

    public function serializeDateTime(VisitorInterface $visitor, DateTimeInterface $date, Type $type, Context $context): mixed
    {
        $format = $this->getFormat($type);
        if ($format === 'U') {
            return $visitor->visitInteger($date->getTimestamp(), $type, $context);
        }

        return $this->serialize($visitor, $date->format($this->getFormat($type)), $type, $context);
    }

    public function serializeDateInterval(VisitorInterface $visitor, DateInterval $date, Type $type, Context $context): mixed
    {
        return $this->serialize($visitor, $this->formatInterval($date), $type, $context);
    }

    public function deserializeDateInterval(VisitorInterface $visitor, mixed $value): DateInterval
    {
        $negative = false;
        if (isset($value[0]) && ($value[0] === '+' || $value[0] === '-')) {
            $negative = $value[0] === '-';
            $value = substr($value, 1);
        }

        $interval = new DateInterval($value);
        if ($negative) {
            $interval->invert = 1;
        }

        return $interval;
    }

    public function deserializeDateTime(VisitorInterface $visitor, mixed $data, Type $type): DateTimeInterface|null
    {
        return $this->deserializeDateTimeInterface(DateTime::class, $data, $type);
    }

    public function deserializeDateTimeImmutable(VisitorInterface $visitor, mixed $data, Type $type): DateTimeInterface|null
    {
        return $this->deserializeDateTimeInterface(DateTimeImmutable::class, $data, $type);
    }

    public function deserializeChronos(VisitorInterface $visitor, mixed $date, Type $type): Chronos|null
    {
        $date = $this->deserializeDateTimeImmutable($visitor, $date, $type);
        if ($date === null) {
            return null;
        }

        return Chronos::instance($date);
    }

    /** @internal */
    public function formatInterval(DateInterval $dateInterval): string
    {
        $formatted = $dateInterval->format(self::DATEINTERVAL_FORMAT);
        $formatted = preg_replace('/(?<=\D)0[A-Z]/', '', $formatted);
        assert(is_string($formatted));

        $formatted = str_replace('+', '', $formatted);

        if ($formatted === 'PT') {
            $formatted = 'PT0S';
        }

        if (str_ends_with($formatted, 'T')) {
            $formatted = substr($formatted, 0, -1);
        }

        return $formatted;
    }

    private function deserializeDateTimeInterface(string $class, mixed $data, Type $type): DateTimeInterface|null
    {
        if ($data === null) {
            return null;
        }

        $timezone = $type->hasParam(1) ? new DateTimeZone($type->getParam(1)) : $this->defaultTimezone;
        $format = $this->getFormat($type);
        $datetime = $class::createFromFormat($format, (string) $data, $timezone);

        if ($datetime === false) {
            throw new RuntimeException(sprintf('Invalid datetime "%s", expected format %s.', $data, $format));
        }

        return $datetime;
    }

    private function serialize(VisitorInterface $visitor, mixed $data, Type $type, Context $context): mixed
    {
        if ($visitor instanceof XmlSerializationVisitor && $this->xmlCData === false) {
            return $visitor->visitSimpleString($data);
        }

        return $visitor->visitString($data, $type, $context);
    }

    private function getFormat(Type $type): string
    {
        return $type->hasParam(0) ? $type->getParam(0) : $this->defaultFormat;
    }
}
