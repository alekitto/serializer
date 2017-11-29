<?php declare(strict_types=1);

namespace Kcs\Serializer\Handler;

use Kcs\Serializer\Context;
use Kcs\Serializer\Direction;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;

class PropelCollectionHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        $methods = [];

        //Note: issue when handling inheritance
        $collectionTypes = [
            \PropelCollection::class,
            \PropelObjectCollection::class,
            \PropelArrayCollection::class,
            \PropelOnDemandCollection::class,
        ];

        foreach ($collectionTypes as $type) {
            $methods[] = [
                'direction' => Direction::DIRECTION_SERIALIZATION,
                'type' => $type,
                'method' => 'serializeCollection',
            ];

            $methods[] = [
                'direction' => Direction::DIRECTION_DESERIALIZATION,
                'type' => $type,
                'method' => 'deserializeCollection',
            ];
        }

        return $methods;
    }

    public function serializeCollection(VisitorInterface $visitor, \PropelCollection $collection, Type $type, Context $context)
    {
        return $visitor->visitArray($collection->getData(), $type, $context);
    }

    public function deserializeCollection(VisitorInterface $visitor, $data, Type $type, Context $context)
    {
        $collection = new \PropelCollection();
        $collection->setData($visitor->visitArray($data, $type, $context));

        return $collection;
    }
}
