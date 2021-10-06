<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use ArrayAccess;

class AttributesMap implements ArrayAccess
{
    /** @var array<string, mixed> */
    private array $map = [];

    /**
     * Returns an attribute by name.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->map[$key] ?? $default;
    }

    /**
     * Set an attribute into the map.
     */
    public function set(string $key, mixed $value): void
    {
        $this->map[$key] = $value;
    }

    /**
     * Returns TRUE if attribute is set.
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
     * {@inheritDoc}
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->map[$offset]);
    }
}
