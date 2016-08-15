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

namespace Kcs\Serializer\Type;

use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Type\Parser\Parser;

/**
 * Serialized type representation
 */
class Type
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $params;

    /**
     * Type constructor
     *
     * @param string $name
     * @param array $params
     */
    public function __construct($name, array $params = [])
    {
        $this->name = $name;
        $this->params = $params;
    }

    /**
     * Parse a type string and return a Type instance
     *
     * @param $type
     * @return Type
     */
    public static function parse($type)
    {
        static $parser = null;
        if (null === $parser) {
            $parser = new Parser();
        }

        return $parser->parse($type);
    }

    /**
     * Create a new Type from an object or a class string
     *
     * @param mixed $object
     * @return Type
     */
    public static function from($object)
    {
        if ($object instanceof self) {
            return $object;
        }

        if (is_object($object)) {
            $object = get_class($object);
        }

        if (! is_string($object)) {
            throw new InvalidArgumentException('Cannot create a type from '.gettype($object));
        }

        return new self($object);
    }

    /**
     * @return Type
     */
    public static function null()
    {
        static $nullType = null;
        if (null === $nullType) {
            $nullType = new static('NULL');
        }

        return $nullType;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Type
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param array $params
     * @return Type
     */
    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }

    public function countParams()
    {
        return count($this->params);
    }

    /**
     * Check if this type represents $class
     *
     * @param $class
     * @return bool
     */
    public function is($class)
    {
        return $this->name === $class;
    }

    /**
     * Returns if this type has param with index $index
     *
     * @param $index
     * @return bool
     */
    public function hasParam($index)
    {
        return isset($this->params[$index]);
    }

    /**
     * Return the param $index
     *
     * @param $index
     * @return mixed
     */
    public function getParam($index)
    {
        return $this->params[$index];
    }
}
