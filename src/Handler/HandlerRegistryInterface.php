<?php declare(strict_types=1);

namespace Kcs\Serializer\Handler;

/**
 * Handler Registry Interface.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface HandlerRegistryInterface
{
    /**
     * @param SubscribingHandlerInterface $handler
     */
    public function registerSubscribingHandler(SubscribingHandlerInterface $handler);

    /**
     * Registers a handler in the registry.
     *
     * @param int      $direction one of the GraphNavigator::DIRECTION_??? constants
     * @param string   $typeName
     * @param callable $handler   function(VisitorInterface, mixed $data, array $type): mixed
     */
    public function registerHandler($direction, $typeName, callable $handler);

    /**
     * @param int    $direction one of the GraphNavigator::DIRECTION_??? constants
     * @param string $typeName
     *
     * @return callable|null
     */
    public function getHandler($direction, $typeName);
}
