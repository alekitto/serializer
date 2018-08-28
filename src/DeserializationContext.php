<?php declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\Metadata\PropertyMetadata;

class DeserializationContext extends Context
{
    /**
     * @var int
     */
    private $depth = 0;

    /**
     * {@inheritdoc}
     */
    public function getDirection(): int
    {
        return Direction::DIRECTION_DESERIALIZATION;
    }

    /**
     * {@inheritdoc}
     */
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
            throw new \LogicException('Depth cannot be smaller than zero.');
        }

        --$this->depth;
    }

    /**
     * {@inheritdoc}
     */
    protected function filterPropertyMetadata(PropertyMetadata $propertyMetadata): bool
    {
        if ($propertyMetadata->readOnly) {
            return false;
        }

        return parent::filterPropertyMetadata($propertyMetadata);
    }
}
