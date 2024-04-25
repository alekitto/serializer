<?php

declare(strict_types=1);

namespace Kcs\Serializer\Handler;

use Kcs\Serializer\Context;
use Kcs\Serializer\Direction;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;
use Symfony\Component\Uid;

class SymfonyUidHandler implements SubscribingHandlerInterface
{
    private const UUID_TYPES = [
        Uid\AbstractUid::class,
        Uid\Uuid::class,
        Uid\NilUuid::class,
        Uid\MaxUuid::class,
        Uid\UuidV1::class,
        Uid\UuidV3::class,
        Uid\UuidV4::class,
        Uid\UuidV5::class,
        Uid\UuidV6::class,
        Uid\UuidV7::class,
        Uid\UuidV8::class,
    ];

    private const ULID_TYPES = [
        Uid\Ulid::class,
        Uid\NilUlid::class,
        Uid\MaxUlid::class,
    ];

    /**
     * {@inheritDoc}
     */
    public static function getSubscribingMethods(): iterable
    {
        foreach (self::UUID_TYPES as $type) {
            yield [
                'direction' => Direction::Serialization,
                'type' => $type,
                'method' => 'serializeUuid',
            ];

            yield [
                'direction' => Direction::Deserialization,
                'type' => $type,
                'method' => 'deserializeUuid',
            ];
        }

        foreach (self::ULID_TYPES as $type) {
            yield [
                'direction' => Direction::Serialization,
                'type' => $type,
                'method' => 'serializeUlid',
            ];

            yield [
                'direction' => Direction::Deserialization,
                'type' => $type,
                'method' => 'deserializeUlid',
            ];
        }
    }

    /**
     * Serializes an Uuid object into a string.
     */
    public function serializeUuid(VisitorInterface $visitor, Uid\AbstractUid|null $uuid, Type $type, Context $context): mixed
    {
        if ($uuid === null) {
            return $visitor->visitNull(null, Type::null(), $context);
        }

        return $visitor->visitString($uuid->toRfc4122(), $type, $context);
    }

    /**
     * Converts a string representation into an Uuid object.
     */
    public function deserializeUuid(VisitorInterface $visitor, mixed $data, Type $type, Context $context): Uid\AbstractUid|null
    {
        if (empty($data)) {
            return $visitor->visitNull(null, Type::null(), $context);
        }

        return Uid\Uuid::fromString($data);
    }

    /**
     * Serializes an Ulid object into a string.
     */
    public function serializeUlid(VisitorInterface $visitor, Uid\AbstractUid|null $uuid, Type $type, Context $context): mixed
    {
        if ($uuid === null) {
            return $visitor->visitNull(null, Type::null(), $context);
        }

        return $visitor->visitString($uuid->toBase32(), $type, $context);
    }

    /**
     * Converts a string representation into an Ulid object.
     */
    public function deserializeUlid(VisitorInterface $visitor, mixed $data, Type $type, Context $context): Uid\AbstractUid|null
    {
        if (empty($data)) {
            return $visitor->visitNull(null, Type::null(), $context);
        }

        return Uid\Ulid::fromString($data);
    }
}
