<?php declare(strict_types=1);
/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 * Copyright 2016 Alessandro Chitolina <alekitto@gmail.com>
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

use Kcs\Serializer\Context;
use Kcs\Serializer\Direction;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;

class PropelCollectionHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        $methods = [];

        //Note: issue when handling inheritance
        $collectionTypes = [
            \PropelCollection::class,
            \PropelObjectCollection::class,
            \PropelArrayCollection::class,
            \PropelOnDemandCollection::class,
        ];

        foreach ($collectionTypes as $type) {
            $methods[] = [
                'direction' => Direction::DIRECTION_SERIALIZATION,
                'type' => $type,
                'method' => 'serializeCollection',
            ];

            $methods[] = [
                'direction' => Direction::DIRECTION_DESERIALIZATION,
                'type' => $type,
                'method' => 'deserializeCollection',
            ];
        }

        return $methods;
    }

    public function serializeCollection(VisitorInterface $visitor, \PropelCollection $collection, Type $type, Context $context)
    {
        return $visitor->visitArray($collection->getData(), $type, $context);
    }

    public function deserializeCollection(VisitorInterface $visitor, $data, Type $type, Context $context)
    {
        $collection = new \PropelCollection();
        $collection->setData($visitor->visitArray($data, $type, $context));

        return $collection;
    }
}
