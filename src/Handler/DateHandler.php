<?php declare(strict_types=1);

namespace Kcs\Serializer\Handler;

use Cake\Chronos\Chronos;
use DateTimeZone;
use Kcs\Serializer\Context;
use Kcs\Serializer\Direction;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;
use Kcs\Serializer\XmlSerializationVisitor;

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
            'type' => \DateTime::class,
            'direction' => Direction::DIRECTION_DESERIALIZATION,
            'method' => 'deserializeDateTime',
        ];

        foreach ([\DateTimeImmutable::class, \DateTimeInterface::class] as $class) {
            yield [
                'type' => $class,
                'direction' => Direction::DIRECTION_DESERIALIZATION,
                'method' => 'deserializeDateTimeImmutable',
            ];
        }

        foreach ([\DateTime::class, \DateTimeImmutable::class, \DateTimeInterface::class, \Safe\DateTime::class, \Safe\DateTimeImmutable::class] as $class) {
            yield [
                'type' => $class,
                'direction' => Direction::DIRECTION_SERIALIZATION,
                'method' => 'serializeDateTime',
            ];
        }

        yield [
            'type' => \DateInterval::class,
            'direction' => Direction::DIRECTION_SERIALIZATION,
            'method' => 'serializeDateInterval',
        ];

        yield [
            'type' => \DateInterval::class,
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
        string $defaultFormat = \DateTime::ATOM,
        string $defaultTimezone = 'UTC',
        bool $xmlCData = true
    ) {
        $this->defaultFormat = $defaultFormat;
        $this->defaultTimezone = new DateTimeZone($defaultTimezone);
        $this->xmlCData = $xmlCData;
    }

    public function serializeDateTime(VisitorInterface $visitor, \DateTimeInterface $date, Type $type, Context $context)
    {
        $format = $this->getFormat($type);
        if ('U' === $format) {
            return $visitor->visitInteger($date->getTimestamp(), $type, $context);
        }

        return $this->serialize($visitor, $date->format($this->getFormat($type)), $type, $context);
    }

    public function serializeDateInterval(VisitorInterface $visitor, \DateInterval $date, Type $type, Context $context)
    {
        return $this->serialize($visitor, $this->formatInterval($date), $type, $context);
    }

    public function deserializeDateInterval(VisitorInterface $visitor, $value): \DateInterval
    {
        $negative = false;
        if (isset($value[0]) && ('+' === $value[0] || '-' === $value[0])) {
            $negative = '-' === $value[0];
            $value = \substr($value, 1);
        }

        $interval = new \DateInterval($value);
        if ($negative) {
            $interval->invert = 1;
        }

        return $interval;
    }

    public function deserializeDateTime(VisitorInterface $visitor, $data, Type $type): ?\DateTimeInterface
    {
        return $this->deserializeDateTimeInterface(\DateTime::class, $data, $type);
    }

    public function deserializeDateTimeImmutable(VisitorInterface $visitor, $data, Type $type): ?\DateTimeInterface
    {
        return $this->deserializeDateTimeInterface(\DateTimeImmutable::class, $data, $type);
    }

    public function deserializeChronos(VisitorInterface $visitor, $date, Type $type): ?Chronos
    {
        if (null === ($date = $this->deserializeDateTimeImmutable($visitor, $date, $type))) {
            return null;
        }

        return Chronos::instance($date);
    }

    /**
     * @internal
     */
    public function formatInterval(\DateInterval $dateInterval): string
    {
        $formatted = $dateInterval->format(self::DATEINTERVAL_FORMAT);
        $formatted = \preg_replace('/(?<=\D)0[A-Z]/', '', $formatted);
        $formatted = \str_replace('+', '', $formatted);

        if ('PT' === $formatted) {
            $formatted = 'PT0S';
        }

        if ('T' === \substr($formatted, -1)) {
            $formatted = \substr($formatted, 0, -1);
        }

        return $formatted;
    }

    private function deserializeDateTimeInterface(string $class, $data, Type $type): ?\DateTimeInterface
    {
        if (null === $data) {
            return null;
        }

        $timezone = $type->hasParam(1) ? new DateTimeZone($type->getParam(1)) : $this->defaultTimezone;
        $format = $this->getFormat($type);
        $datetime = $class::createFromFormat($format, (string) $data, $timezone);

        if (false === $datetime) {
            throw new RuntimeException(\sprintf('Invalid datetime "%s", expected format %s.', $data, $format));
        }

        return $datetime;
    }

    private function serialize(VisitorInterface $visitor, $data, Type $type, Context $context)
    {
        if ($visitor instanceof XmlSerializationVisitor && false === $this->xmlCData) {
            return $visitor->visitSimpleString($data);
        }

        return $visitor->visitString($data, $type, $context);
    }

    private function getFormat(Type $type): string
    {
        return $type->hasParam(0) ? $type->getParam(0) : $this->defaultFormat;
    }
}
