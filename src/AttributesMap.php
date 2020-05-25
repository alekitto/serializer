<?php declare(strict_types=1);

namespace Kcs\Serializer;

class AttributesMap implements \ArrayAccess
{
    /**
     * @var array<string, mixed>
     */
    private array $map = [];

    /**
     * Returns an attribute by name.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->map[$key] ?? $default;
    }

    /**
     * Set an attribute into the map.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     */
    public function set(string $key, $value)
    {
        return $this->map[$key] = $value;
    }

    /**
     * Returns TRUE if attribute is set.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->map[$key]);
    }

    /**
     * Returns a copy of the array map.
     *
     * @return array<string, mixed>
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
    public function offsetUnset($offset): void
    {
        unset($this->map[$offset]);
    }
}
