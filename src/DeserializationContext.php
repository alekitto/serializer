<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\Metadata\PropertyMetadata;
use LogicException;

class DeserializationContext extends Context
{
    public Direction $direction = Direction::Deserialization;
    public bool $ignoreCase = false;
    private int $depth = 0;

    /** @inheritDoc */
    public function createChildContext(array $attributes = []): static
    {
        $context = parent::createChildContext($attributes);
        $context->ignoreCase = $this->ignoreCase;

        return $context;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function increaseDepth(): void
    {
        ++$this->depth;
    }

    public function decreaseDepth(): void
    {
        if ($this->depth <= 0) {
            throw new LogicException('Depth cannot be smaller than zero.');
        }

        --$this->depth;
    }

    protected function filterPropertyMetadata(PropertyMetadata $propertyMetadata): bool
    {
        if ($propertyMetadata->immutable) {
            return false;
        }

        return parent::filterPropertyMetadata($propertyMetadata);
    }
}
