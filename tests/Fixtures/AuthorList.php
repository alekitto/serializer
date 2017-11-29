<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation as Serializer;

/**
 * An array-acting object that holds many author instances.
 *
 * @Serializer\AccessType("property")
 */
class AuthorList implements \IteratorAggregate, \Countable, \ArrayAccess
{
    /**
     * @Serializer\Type("array<Kcs\Serializer\Tests\Fixtures\Author>")
     *
     * @var array
     */
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
    public function getIterator()
    {
        return new \ArrayIterator($this->authors);
    }

    /**
     * @see Countable
     */
    public function count()
    {
        return count($this->authors);
    }

    /**
     * @see ArrayAccess
     */
    public function offsetExists($offset)
    {
        return isset($this->authors[$offset]);
    }

    /**
     * @see ArrayAccess
     */
    public function offsetGet($offset)
    {
        return isset($this->authors[$offset]) ? $this->authors[$offset] : null;
    }

    /**
     * @see ArrayAccess
     */
    public function offsetSet($offset, $value)
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
    public function offsetUnset($offset)
    {
        unset($this->authors[$offset]);
    }
}
