<?php

declare(strict_types=1);

namespace Kcs\Serializer\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\PersistentCollection as MongoDBPersistentCollection;
use Doctrine\ODM\PHPCR\PersistentCollection as PHPCRPersistentCollection;
use Doctrine\ORM\PersistentCollection;
use Kcs\Serializer\Context;
use Kcs\Serializer\Direction;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;

class ArrayCollectionHandler implements SubscribingHandlerInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribingMethods(): iterable
    {
        $methods = [];
        $collectionTypes = [
            'ArrayCollection',
            ArrayCollection::class,
            PersistentCollection::class,
            MongoDBPersistentCollection::class, // @phpstan-ignore-line
            PHPCRPersistentCollection::class,
        ];

        foreach ($collectionTypes as $type) {
            $methods[] = [
                'direction' => Direction::Serialization,
                'type' => $type,
                'method' => 'serializeCollection',
            ];

            $methods[] = [
                'direction' => Direction::Deserialization,
                'type' => $type,
                'method' => 'deserializeCollection',
            ];
        }

        return $methods;
    }

    /** @param Collection<mixed> $collection */
    public function serializeCollection(VisitorInterface $visitor, Collection $collection, Type $type, Context $context): mixed
    {
        if ($type->countParams() === 1) {
            return $visitor->visitArray($collection->toArray(), $type, $context);
        }

        return $visitor->visitHash($collection->toArray(), $type, $context);
    }

    /** @return Collection<mixed> */
    public function deserializeCollection(VisitorInterface $visitor, mixed $data, Type $type, Context $context): Collection
    {
        if ($type->countParams() === 1) {
            return new ArrayCollection($visitor->visitArray($data, $type, $context));
        }

        return new ArrayCollection($visitor->visitHash($data, $type, $context));
    }
}
