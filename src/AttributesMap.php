<?php

/*
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

class AttributesMap
{
    private $map = [];

    /**
     * Returns an attribute by name
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return isset($this->map[$key]) ? $this->map[$key] : $default;
    }

    /**
     * Set an attribute into the map
     *
     * @param string $key
     * @param mixed $value
     *
     * @return mixed
     */
    public function set($key, $value)
    {
        return $this->map[$key] = $value;
    }

    /**
     * Returns TRUE if attribute is set
     *
     * @param $key
     *
     * @return bool
     */
    public function has($key)
    {
        return isset($this->map[$key]);
    }

    /**
     * Returns a copy of the array map
     *
     * @return array
     */
    public function all()
    {
        return $this->map;
    }
}
