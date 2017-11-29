<?php declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Metadata\Factory\MetadataFactoryInterface;

class SerializationContext extends Context
{
    /**
     * @var \SplObjectStorage
     */
    private $visitingSet;

    public function initialize($format, VisitorInterface $visitor, GraphNavigator $navigator, MetadataFactoryInterface $factory)
    {
        parent::initialize($format, $visitor, $navigator, $factory);

        $this->visitingSet = new \SplObjectStorage();
    }

    public function startVisiting($object)
    {
        $this->visitingSet->attach($object);
    }

    public function stopVisiting($object)
    {
        $this->visitingSet->detach($object);
    }

    public function isVisiting($object)
    {
        return $this->visitingSet->contains($object);
    }

    public function getDirection()
    {
        return Direction::DIRECTION_SERIALIZATION;
    }

    public function getDepth()
    {
        return $this->visitingSet->count();
    }
}
