<?php declare(strict_types=1);

namespace Kcs\Serializer\Handler;

interface SubscribingHandlerInterface
{
    /**
     * Return format:
     *
     *      yield [
     *          'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
     *          'type' => 'DateTime',
     *          'method' => 'serializeDateTimeToJson',
     *      ];
     *
     * The direction and method keys can be omitted.
     *
     * @return iterable
     */
    public function getSubscribingMethods();
}
