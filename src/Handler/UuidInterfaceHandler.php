<?php declare(strict_types=1);

namespace Kcs\Serializer\Handler;

use Kcs\Serializer\Context;
use Kcs\Serializer\Direction;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;
use Ramsey\Uuid\DegradedUuid;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UuidInterfaceHandler implements SubscribingHandlerInterface
{
    const TYPES = [
        UuidInterface::class,
        Uuid::class,
        DegradedUuid::class,
    ];

    /**
     * {@inheritdoc}
     */
    public static function getSubscribingMethods()
    {
        foreach (self::TYPES as $type) {
            yield [
                'direction' => Direction::DIRECTION_SERIALIZATION,
                'type' => $type,
                'method' => 'serialize',
            ];
        }
    }

    public function serialize(VisitorInterface $visitor, ? UuidInterface $uuid, Type $type, Context $context)
    {
        if (null === $uuid) {
            return $visitor->visitNull(null, Type::null(), $context);
        }

        return $visitor->visitString($uuid->toString(), $type, $context);
    }
}
