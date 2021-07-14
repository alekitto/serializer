<?php

declare(strict_types=1);

namespace Kcs\Serializer\Type;

use JsonSerializable;
use Kcs\Metadata\MetadataInterface;
use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Type\Parser\Parser;

use function count;
use function get_class;
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
    public string $name;
    public ?MetadataInterface $metadata = null;

    /** @var array<string|int, mixed> */
    private array $params;

    /**
     * @param array<string|int, mixed> $params
     */
    public function __construct(string $name, array $params = [])
    {
        $this->name = $name;
        $this->params = $params;
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
     *
     * @param mixed $object
     */
    public static function from($object): self
    {
        if ($object instanceof self) {
            return $object;
        }

        if (is_object($object)) {
            $object = get_class($object);
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

    /**
     * @return array<string|int, mixed>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param array<string|int, mixed> $params
     */
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
     *
     * @param string|int $index
     */
    public function hasParam($index): bool
    {
        return isset($this->params[$index]);
    }

    /**
     * Return the param $index.
     *
     * @param string|int $index
     *
     * @return mixed
     */
    public function getParam($index)
    {
        return $this->params[$index];
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'params' => json_decode(json_encode($this->params, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR),
        ];
    }
}
