<?php declare(strict_types=1);

namespace Kcs\Serializer\Type;

use Kcs\Metadata\MetadataInterface;
use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Type\Parser\Parser;

/**
 * Serialized type representation.
 *
 * @internal
 */
/* final */ class Type
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var array
     */
    private $params;

    /**
     * @var MetadataInterface
     */
    public $metadata;

    /**
     * Type constructor.
     *
     * @param string $name
     * @param array  $params
     */
    public function __construct(string $name, array $params = [])
    {
        $this->name = $name;
        $this->params = $params;
    }

    /**
     * Parse a type string and return a Type instance.
     *
     * @param $type
     *
     * @return Type
     */
    public static function parse(string $type): self
    {
        static $parser = null;
        if (null === $parser) {
            $parser = new Parser();
        }

        return $parser->parse($type);
    }

    /**
     * Create a new Type from an object or a class string.
     *
     * @param mixed $object
     *
     * @return Type
     */
    public static function from($object): self
    {
        if ($object instanceof self) {
            return $object;
        }

        if (\is_object($object)) {
            $object = \get_class($object);
        }

        if (! \is_string($object)) {
            throw new InvalidArgumentException('Cannot create a type from '.\gettype($object));
        }

        return new self($object);
    }

    /**
     * @return Type
     */
    public static function null(): self
    {
        static $nullType = null;
        if (null === $nullType) {
            $nullType = new self('NULL');
        }

        return $nullType;
    }

    /**
     * @return string
     *
     * @deprecated
     */
    public function getName(): string
    {
        @trigger_error('Using Type::getName() is deprecated. Please use name property directly.', E_USER_DEPRECATED);

        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     *
     * @deprecated
     */
    public function setName(string $name): self
    {
        @trigger_error('Using Type::setName() is deprecated. Please use name property directly.', E_USER_DEPRECATED);

        $this->name = $name;
        $this->metadata = null;

        return $this;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    public function setParams(array $params): self
    {
        $this->params = $params;

        return $this;
    }

    public function countParams(): int
    {
        return \count($this->params);
    }

    /**
     * Check if this type represents $class.
     *
     * @param string $class
     *
     * @return bool
     */
    public function is(string $class): bool
    {
        return $this->name === $class;
    }

    /**
     * Returns if this type has param with index $index.
     *
     * @param string|int $index
     *
     * @return bool
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
     * @return MetadataInterface
     *
     * @deprecated
     */
    public function getMetadata(): ?MetadataInterface
    {
        @trigger_error('Using Type::getMetadata() is deprecated. Please use metadata property directly.', E_USER_DEPRECATED);

        return $this->metadata;
    }

    /**
     * @param MetadataInterface $metadata
     *
     * @return $this
     *
     * @deprecated
     */
    public function setMetadata(MetadataInterface $metadata): self
    {
        @trigger_error('Using Type::setMetadata() is deprecated. Please use metadata property directly.', E_USER_DEPRECATED);
        $this->metadata = $metadata;

        return $this;
    }
}
