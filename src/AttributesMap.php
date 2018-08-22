<?php declare(strict_types=1);

namespace Kcs\Serializer;

class AttributesMap implements \ArrayAccess
{
    private $map = [];

    /**
     * Returns an attribute by name.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return isset($this->map[$key]) ? $this->map[$key] : $default;
    }

    /**
     * Set an attribute into the map.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     */
    public function set($key, $value)
    {
        return $this->map[$key] = $value;
    }

    /**
     * Returns TRUE if attribute is set.
     *
     * @param $key
     *
     * @return bool
     */
    public function has($key): bool
    {
        return isset($this->map[$key]);
    }

    /**
     * Returns a copy of the array map.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->map;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        return $this->set($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->map[$offset]);
    }
}
