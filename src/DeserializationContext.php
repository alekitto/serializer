<?php declare(strict_types=1);

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 * Copyright 2017 Alessandro Chitolina <alekitto@gmail.com>
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

use Kcs\Serializer\Metadata\PropertyMetadata;

class DeserializationContext extends Context
{
    private $depth = 0;

    public function getDirection()
    {
        return Direction::DIRECTION_DESERIALIZATION;
    }

    public function getDepth()
    {
        return $this->depth;
    }

    public function increaseDepth()
    {
        $this->depth += 1;
    }

    public function decreaseDepth()
    {
        if ($this->depth <= 0) {
            throw new \LogicException('Depth cannot be smaller than zero.');
        }

        $this->depth -= 1;
    }

    protected function filterPropertyMetadata(PropertyMetadata $propertyMetadata)
    {
        if ($propertyMetadata->readOnly) {
            return false;
        }

        return parent::filterPropertyMetadata($propertyMetadata);
    }
}
