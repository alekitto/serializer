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

use function Safe\preg_replace;
use function Safe\sprintf;
use function Safe\substr;
use function str_replace;

class DateHandler implements SubscribingHandlerInterface
{
    private const DATEINTERVAL_FORMAT = '%RP%yY%mM%dDT%hH%iM%sS';

    private string $defaultFormat;
    private DateTimeZone $defaultTimezone;
    private bool $xmlCData;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribingMethods(): iterable
    {
        yield [
            'type' => DateTime::class,
            'direction' => Direction::DIRECTION_DESERIALIZATION,
            'method' => 'deserializeDateTime',
        ];

        foreach ([DateTimeImmutable::class, DateTimeInterface::class] as $class) {
            yield [
                'type' => $class,
                'direction' => Direction::DIRECTION_DESERIALIZATION,
                'method' => 'deserializeDateTimeImmutable',
            ];
        }

        foreach ([DateTime::class, DateTimeImmutable::class, DateTimeInterface::class, \Safe\DateTime::class, \Safe\DateTimeImmutable::class] as $class) {
            yield [
                'type' => $class,
                'direction' => Direction::DIRECTION_SERIALIZATION,
                'method' => 'serializeDateTime',
            ];
        }

        yield [
            'type' => DateInterval::class,
            'direction' => Direction::DIRECTION_SERIALIZATION,
            'method' => 'serializeDateInterval',
        ];

        yield [
            'type' => DateInterval::class,
            'direction' => Direction::DIRECTION_DESERIALIZATION,
            'method' => 'deserializeDateInterval',
        ];

        yield [
            'type' => Chronos::class,
            'direction' => Direction::DIRECTION_SERIALIZATION,
            'method' => 'serializeDateTime',
        ];

        yield [
            'type' => Chronos::class,
            'direction' => Direction::DIRECTION_DESERIALIZATION,
            'method' => 'deserializeChronos',
        ];
    }

    public function __construct(
        string $defaultFormat = DateTimeInterface::ATOM,
        string $defaultTimezone = 'UTC',
        bool $xmlCData = true
    ) {
        $this->defaultFormat = $defaultFormat;
        $this->defaultTimezone = new DateTimeZone($defaultTimezone);
        $this->xmlCData = $xmlCData;
    }

    /**
     * @return mixed
     */
    public function serializeDateTime(VisitorInterface $visitor, DateTimeInterface $date, Type $type, Context $context)
    {
        $format = $this->getFormat($type);
        if ($format === 'U') {
            return $visitor->visitInteger($date->getTimestamp(), $type, $context);
        }

        return $this->serialize($visitor, $date->format($this->getFormat($type)), $type, $context);
    }

    /**
     * @return mixed
     */
    public function serializeDateInterval(VisitorInterface $visitor, DateInterval $date, Type $type, Context $context)
    {
        return $this->serialize($visitor, $this->formatInterval($date), $type, $context);
    }

    /**
     * @param mixed $value
     */
    public function deserializeDateInterval(VisitorInterface $visitor, $value): DateInterval
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

    /**
     * @param mixed $data
     */
    public function deserializeDateTime(VisitorInterface $visitor, $data, Type $type): ?DateTimeInterface
    {
        return $this->deserializeDateTimeInterface(DateTime::class, $data, $type);
    }

    /**
     * @param mixed $data
     */
    public function deserializeDateTimeImmutable(VisitorInterface $visitor, $data, Type $type): ?DateTimeInterface
    {
        return $this->deserializeDateTimeInterface(DateTimeImmutable::class, $data, $type);
    }

    /**
     * @param mixed $date
     */
    public function deserializeChronos(VisitorInterface $visitor, $date, Type $type): ?Chronos
    {
        $date = $this->deserializeDateTimeImmutable($visitor, $date, $type);
        if ($date === null) {
            return null;
        }

        return Chronos::instance($date);
    }

    /**
     * @internal
     */
    public function formatInterval(DateInterval $dateInterval): string
    {
        $formatted = $dateInterval->format(self::DATEINTERVAL_FORMAT);
        $formatted = preg_replace('/(?<=\D)0[A-Z]/', '', $formatted);
        $formatted = str_replace('+', '', $formatted);

        if ($formatted === 'PT') {
            $formatted = 'PT0S';
        }

        if (substr($formatted, -1) === 'T') {
            $formatted = substr($formatted, 0, -1);
        }

        return $formatted;
    }

    /**
     * @param mixed $data
     */
    private function deserializeDateTimeInterface(string $class, $data, Type $type): ?DateTimeInterface
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

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    private function serialize(VisitorInterface $visitor, $data, Type $type, Context $context)
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
