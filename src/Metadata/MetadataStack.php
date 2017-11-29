<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata;

/**
 * @author Alessandro Chitolina <alekitto@gmail.com>
 */
class MetadataStack implements \IteratorAggregate, \Countable
{
    /**
     * @var \SplStack
     */
    private $stack;

    /**
     * @var string[]
     */
    private $currentPath;

    public function __construct()
    {
        $this->stack = new \SplStack();
        $this->currentPath = [];
    }

    public function push(PropertyMetadata $metadata)
    {
        $this->stack->push($metadata);
        $this->currentPath[] = $metadata->name;
    }

    public function pop()
    {
        $metadata = $this->stack->pop();
        array_pop($this->currentPath);

        return $metadata;
    }

    /**
     * Get current property path.
     *
     * @return string[]
     */
    public function getPath()
    {
        return $this->currentPath;
    }

    public function getCurrent()
    {
        return $this->stack->isEmpty() ? null : $this->stack->top();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->stack;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->stack->count();
    }
}
