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

namespace Kcs\Serializer;

use Kcs\Metadata\Factory\MetadataFactoryInterface;

class SerializationContext extends Context
{
    private $visitingSet;

    public static function create()
    {
        return new self();
    }

    public function initialize($format, VisitorInterface $visitor, GraphNavigator $navigator, MetadataFactoryInterface $factory)
    {
        parent::initialize($format, $visitor, $navigator, $factory);

        $this->visitingSet = array();
    }

    public function startVisiting($object)
    {
        $hash = spl_object_hash($object);
        $this->visitingSet[$hash] = true;
    }

    public function stopVisiting($object)
    {
        $hash = spl_object_hash($object);
        unset ($this->visitingSet[$hash]);
    }

    public function isVisiting($object)
    {
        $hash = spl_object_hash($object);
        return isset ($this->visitingSet[$hash]);
    }

    public function getDirection()
    {
        return Direction::DIRECTION_SERIALIZATION;
    }

    public function getDepth()
    {
        return count($this->visitingSet);
    }

    public function getVisitingSet()
    {
        return $this->visitingSet;
    }
}
