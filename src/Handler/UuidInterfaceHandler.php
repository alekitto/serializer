<?php

declare(strict_types=1);

namespace Kcs\Serializer\Handler;

use Kcs\Serializer\Context;
use Kcs\Serializer\Direction;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;
use Ramsey\Uuid\DegradedUuid;
use Ramsey\Uuid\Guid\Guid;
use Ramsey\Uuid\Lazy\LazyUuidFromString;
use Ramsey\Uuid\Nonstandard\UuidV6;
use Ramsey\Uuid\Rfc4122;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UuidInterfaceHandler implements SubscribingHandlerInterface
{
    private const TYPES = [
        UuidInterface::class,
        Uuid::class,
        Guid::class,
        DegradedUuid::class,
        Rfc4122\NilUuid::class,
        Rfc4122\UuidV1::class,
        Rfc4122\UuidV2::class,
        Rfc4122\UuidV3::class,
        Rfc4122\UuidV4::class,
        Rfc4122\UuidV5::class,
        UuidV6::class,
        LazyUuidFromString::class,
    ];

    /**
     * {@inheritDoc}
     */
    public static function getSubscribingMethods(): iterable
    {
        foreach (self::TYPES as $type) {
            yield [
                'direction' => Direction::Serialization,
                'type' => $type,
                'method' => 'serialize',
            ];

            yield [
                'direction' => Direction::Deserialization,
                'type' => $type,
                'method' => 'deserialize',
            ];
        }
    }

    /**
     * Serializes an Uuid object into a string.
     */
    public function serialize(VisitorInterface $visitor, UuidInterface|null $uuid, Type $type, Context $context): mixed
    {
        if ($uuid === null) {
            return $visitor->visitNull(null, Type::null(), $context);
        }

        return $visitor->visitString($uuid->toString(), $type, $context);
    }

    /**
     * Converts a string representation into an Uuid object.
     */
    public function deserialize(VisitorInterface $visitor, mixed $data, Type $type, Context $context): UuidInterface|null
    {
        if (empty($data)) {
            return $visitor->visitNull(null, Type::null(), $context);
        }

        return Uuid::fromString($data);
    }
}
