<?php

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

namespace Kcs\Serializer;

use Kcs\Metadata\Factory\MetadataFactoryInterface;

class SerializationContext extends Context
{
    /**
     * @var \SplObjectStorage
     */
    private $visitingSet;

    public function initialize($format, VisitorInterface $visitor, GraphNavigator $navigator, MetadataFactoryInterface $factory)
    {
        parent::initialize($format, $visitor, $navigator, $factory);

        $this->visitingSet = new \SplObjectStorage();
    }

    public function startVisiting($object)
    {
        $this->visitingSet->attach($object);
    }

    public function stopVisiting($object)
    {
        $this->visitingSet->detach($object);
    }

    public function isVisiting($object)
    {
        return $this->visitingSet->contains($object);
    }

    public function getDirection()
    {
        return Direction::DIRECTION_SERIALIZATION;
    }

    public function getDepth()
    {
        return $this->visitingSet->count();
    }
}
