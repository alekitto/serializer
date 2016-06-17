<?php

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
