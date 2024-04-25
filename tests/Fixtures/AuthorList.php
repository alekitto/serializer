<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation as Serializer;
use Kcs\Serializer\Metadata\Access;
use Traversable;

/**
 * An array-acting object that holds many author instances.
 */
#[Serializer\AccessType(Access\Type::Property)]
class AuthorList implements \IteratorAggregate, \Countable, \ArrayAccess
{
    /**
     * @var array
     */
    #[Serializer\Type('array<' . Author::class . '>')]
    protected $authors = [];

    /**
     * @param Author $author
     */
    public function add(Author $author)
    {
        $this->authors[] = $author;
    }

    /**
     * @see IteratorAggregate
     */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->authors);
    }

    /**
     * @see Countable
     */
    public function count(): int
    {
        return \count($this->authors);
    }

    /**
     * @see ArrayAccess
     */
    public function offsetExists($offset): bool
    {
        return isset($this->authors[$offset]);
    }

    /**
     * @see ArrayAccess
     */
    public function offsetGet($offset): mixed
    {
        return $this->authors[$offset] ?? null;
    }

    /**
     * @see ArrayAccess
     */
    public function offsetSet($offset, $value): void
    {
        if (null === $offset) {
            $this->authors[] = $value;
        } else {
            $this->authors[$offset] = $value;
        }
    }

    /**
     * @see ArrayAccess
     */
    public function offsetUnset($offset): void
    {
        unset($this->authors[$offset]);
    }
}
