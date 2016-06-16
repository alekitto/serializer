<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 * Modifications copyright (c) 2016 Alessandro Chitolina <alekitto@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Kcs\Serializer\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ODM\MongoDB\PersistentCollection as MongoBDPersistentCollection;
use Doctrine\ODM\PHPCR\PersistentCollection as PHPCRPersistentCollection;
use Kcs\Serializer\Context;
use Kcs\Serializer\GraphNavigator;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;
use Doctrine\Common\Collections\Collection;

class ArrayCollectionHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        $methods = [];
        $collectionTypes = [
            'ArrayCollection',
            ArrayCollection::class,
            PersistentCollection::class,
            MongoDBPersistentCollection::class,
            PHPCRPersistentCollection::class,
        ];

        foreach ($collectionTypes as $type) {
            $methods[] = [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'type' => $type,
                'method' => 'serializeCollection',
            ];

            $methods[] = [
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'type' => $type,
                'method' => 'deserializeCollection',
            ];
        }

        return $methods;
    }

    public function serializeCollection(VisitorInterface $visitor, Collection $collection, Type $type, Context $context)
    {
        return $visitor->visitArray($collection->toArray(), $type, $context);
    }

    public function deserializeCollection(VisitorInterface $visitor, $data, Type $type, Context $context)
    {
        return new ArrayCollection($visitor->visitArray($data, $type, $context));
    }
}
