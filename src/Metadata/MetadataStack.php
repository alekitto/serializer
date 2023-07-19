<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata;

use Countable;
use IteratorAggregate;
use SplStack;
use Traversable;

use function array_pop;

class MetadataStack implements IteratorAggregate, Countable
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

    public function pop(): PropertyMetadata|null
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

    public function pushIndexPath(string $index): void
    {
        $this->currentPath[] = '[' . $index . ']';
    }

    public function popIndexPath(): void
    {
        array_pop($this->currentPath);
    }

    public function getCurrent(): PropertyMetadata|null
    {
        return $this->stack->isEmpty() ? null : $this->stack->top();
    }

    /** @return Traversable<PropertyMetadata> */
    public function getIterator(): Traversable
    {
        return $this->stack;
    }

    public function count(): int
    {
        return $this->stack->count();
    }
}
