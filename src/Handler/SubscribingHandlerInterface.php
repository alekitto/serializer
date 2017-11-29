<?php declare(strict_types=1);

namespace Kcs\Serializer\Handler;

interface SubscribingHandlerInterface
{
    /**
     * Return format:.
     *
     *      array(
     *          array(
     *              'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
     *              'type' => 'DateTime',
     *              'method' => 'serializeDateTimeToJson',
     *          ),
     *      )
     *
     * The direction and method keys can be omitted.
     *
     * @return array
     */
    public static function getSubscribingMethods();
}
