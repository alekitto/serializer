<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\Metadata\PropertyMetadata;
use LogicException;

class DeserializationContext extends Context
{
    public int $direction = Direction::DIRECTION_DESERIALIZATION;
    private int $depth = 0;

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
        if ($propertyMetadata->readOnly) {
            return false;
        }

        return parent::filterPropertyMetadata($propertyMetadata);
    }
}
