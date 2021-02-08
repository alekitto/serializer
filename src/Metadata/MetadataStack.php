<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata;

use SplStack;

class MetadataStack implements \IteratorAggregate, \Countable
{
    /** @var string[] */
    private array $currentPath;
    private SplStack $stack;

    public function __construct()
    {
        $this->stack = new SplStack();
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
        \array_pop($this->currentPath);

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

    public function pushIndexPath(string $index): void
    {
        $this->currentPath[] = '[' . $index . ']';
    }

    public function popIndexPath(): void
    {
        \array_pop($this->currentPath);
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
