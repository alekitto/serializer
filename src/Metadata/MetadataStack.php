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

    public function push(PropertyMetadata $metadata): void
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
    public function getPath(): array
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
    public function getIterator(): iterable
    {
        return $this->stack;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->stack->count();
    }
}
