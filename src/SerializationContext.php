<?php declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Metadata\Factory\MetadataFactoryInterface;

class SerializationContext extends Context
{
    /**
     * @var \SplObjectStorage
     */
    private $visitingSet;

    public function initialize(
        string $format,
        VisitorInterface $visitor,
        GraphNavigator $navigator,
        MetadataFactoryInterface $factory
    ): void {
        parent::initialize($format, $visitor, $navigator, $factory);

        $this->visitingSet = new \SplObjectStorage();
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

    /**
     * {@inheritdoc}
     */
    public function getDirection(): int
    {
        return Direction::DIRECTION_SERIALIZATION;
    }

    /**
     * {@inheritdoc}
     */
    public function getDepth(): int
    {
        return $this->visitingSet->count();
    }
}
