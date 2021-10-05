<?php

declare(strict_types=1);

namespace Kcs\Serializer\Handler;

use Kcs\Serializer\Context;
use Kcs\Serializer\Direction;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;
use PhpCollection\Map;
use PhpCollection\MapInterface;
use PhpCollection\Sequence;
use PhpCollection\SequenceInterface;

use function iterator_to_array;

class PhpCollectionHandler implements SubscribingHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribingMethods(): iterable
    {
        $methods = [];
        $collectionTypes = [
            Sequence::class => 'Sequence',
            Map::class => 'Map',
        ];

        foreach ($collectionTypes as $type => $shortName) {
            $methods[] = [
                'direction' => Direction::DIRECTION_SERIALIZATION,
                'type' => $type,
                'method' => 'serialize' . $shortName,
            ];

            $methods[] = [
                'direction' => Direction::DIRECTION_DESERIALIZATION,
                'type' => $type,
                'method' => 'deserialize' . $shortName,
            ];
        }

        return $methods;
    }

    public function serializeMap(VisitorInterface $visitor, Map $map, Type $type, Context $context): mixed
    {
        return $visitor->visitHash(iterator_to_array($map), $type, $context);
    }

    public function deserializeMap(VisitorInterface $visitor, mixed $data, Type $type, Context $context): MapInterface
    {
        return new Map($visitor->visitHash($data, $type, $context));
    }

    public function serializeSequence(VisitorInterface $visitor, Sequence $sequence, Type $type, Context $context): mixed
    {
        return $visitor->visitArray($sequence->all(), $type, $context);
    }

    public function deserializeSequence(VisitorInterface $visitor, mixed $data, Type $type, Context $context): SequenceInterface
    {
        return new Sequence($visitor->visitArray($data, $type, $context));
    }
}
