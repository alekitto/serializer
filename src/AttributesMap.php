<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use ArrayAccess;
use InvalidArgumentException;

use function is_string;

/** @implements ArrayAccess<string, mixed> */
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

    public function offsetExists(mixed $offset): bool
    {
        if (! is_string($offset)) {
            return false;
        }

        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (! is_string($offset)) {
            return null;
        }

        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (! is_string($offset)) {
            throw new InvalidArgumentException('Attribute key must be a string.');
        }

        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        if (! is_string($offset)) {
            return;
        }

        unset($this->map[$offset]);
    }
}
