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

use Kcs\Serializer\Context;
use Kcs\Serializer\GraphNavigator;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;
use PhpCollection\Map;
use PhpCollection\Sequence;

class PhpCollectionHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        $methods = [];
        $collectionTypes = [
            Sequence::class => 'Sequence',
            Map::class => 'Map',
        ];

        foreach ($collectionTypes as $type => $shortName) {
            $methods[] = [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'type' => $type,
                'method' => 'serialize'.$shortName,
            ];

            $methods[] = [
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'type' => $type,
                'method' => 'deserialize'.$shortName,
            ];
        }

        return $methods;
    }

    public function serializeMap(VisitorInterface $visitor, Map $map, Type $type, Context $context)
    {
        return $visitor->visitArray(iterator_to_array($map), $type, $context);
    }

    public function deserializeMap(VisitorInterface $visitor, $data, Type $type, Context $context)
    {
        return new Map($visitor->visitArray($data, $type, $context));
    }

    public function serializeSequence(VisitorInterface $visitor, Sequence $sequence, Type $type, Context $context)
    {
        return $visitor->visitArray($sequence->all(), $type, $context);
    }

    public function deserializeSequence(VisitorInterface $visitor, $data, Type $type, Context $context)
    {
        return new Sequence($visitor->visitArray($data, $type, $context));
    }
}
