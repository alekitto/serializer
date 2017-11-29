<?php declare(strict_types=1);

namespace Kcs\Serializer;

class AttributesMap
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
    public function has($key)
    {
        return isset($this->map[$key]);
    }

    /**
     * Returns a copy of the array map.
     *
     * @return array
     */
    public function all()
    {
        return $this->map;
    }
}
