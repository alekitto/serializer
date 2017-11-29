<?php declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\Metadata\PropertyMetadata;

class DeserializationContext extends Context
{
    private $depth = 0;

    public function getDirection()
    {
        return Direction::DIRECTION_DESERIALIZATION;
    }

    public function getDepth()
    {
        return $this->depth;
    }

    public function increaseDepth()
    {
        $this->depth += 1;
    }

    public function decreaseDepth()
    {
        if ($this->depth <= 0) {
            throw new \LogicException('Depth cannot be smaller than zero.');
        }

        $this->depth -= 1;
    }

    protected function filterPropertyMetadata(PropertyMetadata $propertyMetadata)
    {
        if ($propertyMetadata->readOnly) {
            return false;
        }

        return parent::filterPropertyMetadata($propertyMetadata);
    }
}
