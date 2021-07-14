<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Metadata\Factory\MetadataFactoryInterface;
use Kcs\Serializer\Type\Type;
use SplObjectStorage;

use function get_class;
use function gettype;
use function is_object;

class SerializationContext extends Context
{
    public int $direction = Direction::DIRECTION_SERIALIZATION;
    private SplObjectStorage $visitingSet;

    public function initialize(
        string $format,
        VisitorInterface $visitor,
        GraphNavigator $navigator,
        MetadataFactoryInterface $factory
    ): void {
        parent::initialize($format, $visitor, $navigator, $factory);

        $this->visitingSet = new SplObjectStorage();
    }

    /**
     * {@inheritdoc}
     */
    public function createChildContext(array $attributes = []): Context
    {
        $obj = parent::createChildContext($attributes);
        $obj->visitingSet = $this->visitingSet;

        return $obj;
    }

    public function startVisiting($object): void
    {
        $this->visitingSet->attach($object);
    }

    public function stopVisiting($object): void
    {
        $this->visitingSet->detach($object);
    }

    public function isVisiting($object): bool
    {
        return $this->visitingSet->contains($object);
    }

    public function getDepth(): int
    {
        return $this->visitingSet->count();
    }

    /**
     * Guesses the serialization type for the given data.
     *
     * @param mixed $data
     */
    public function guessType($data): Type
    {
        if ($data === null) {
            return Type::null();
        }

        return new Type(is_object($data) ? get_class($data) : gettype($data));
    }
}
