<?php

declare(strict_types=1);

namespace Kcs\Serializer\Type;

use JsonSerializable;
use Kcs\Metadata\MetadataInterface;
use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Type\Parser\Parser;

use function count;
use function gettype;
use function is_object;
use function is_string;
use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * Serialized type representation.
 */
final class Type implements JsonSerializable
{
    public MetadataInterface $metadata;

    /** @param array<string|int, mixed> $params */
    public function __construct(public string $name, private array $params = [])
    {
    }

    /**
     * Parse a type string and return a Type instance.
     */
    public static function parse(string $type): self
    {
        static $parser = null;
        if ($parser === null) {
            $parser = new Parser();
        }

        return $parser->parse($type);
    }

    /**
     * Create a new Type from an object or a class string.
     */
    public static function from(mixed $object): self
    {
        if ($object instanceof self) {
            return $object;
        }

        if (is_object($object)) {
            $object = $object::class;
        }

        if (! is_string($object)) {
            throw new InvalidArgumentException('Cannot create a type from ' . gettype($object));
        }

        return new self($object);
    }

    public static function null(): self
    {
        static $nullType = null;
        if ($nullType === null) {
            $nullType = new self('NULL');
        }

        return $nullType;
    }

    /** @return array<string|int, mixed> */
    public function getParams(): array
    {
        return $this->params;
    }

    /** @param array<string|int, mixed> $params */
    public function setParams(array $params): self
    {
        $this->params = $params;

        return $this;
    }

    public function countParams(): int
    {
        return count($this->params);
    }

    /**
     * Check if this type represents $class.
     */
    public function is(string $class): bool
    {
        return $this->name === $class;
    }

    /**
     * Returns if this type has param with index $index.
     */
    public function hasParam(string|int $index): bool
    {
        return isset($this->params[$index]);
    }

    /**
     * Return the param $index.
     */
    public function getParam(string|int $index): mixed
    {
        return $this->params[$index];
    }

    /** @inheritDoc */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'params' => json_decode(json_encode($this->params, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR),
        ];
    }
}
