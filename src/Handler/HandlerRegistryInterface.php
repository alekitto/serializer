<?php declare(strict_types=1);

namespace Kcs\Serializer\Handler;

/**
 * Handler Registry Interface.
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
     * @param callable $handler   function(VisitorInterface, mixed $data, array $type): mixed
     *
     * @return $this
     */
    public function registerHandler(int $direction, string $typeName, $handler): self;

    /**
     * Register a serialization handler.
     */
    public function registerSerializationHandler(SerializationHandlerInterface $handler): self;

    /**
     * Register a deserialization handler.
     */
    public function registerDeserializationHandler(DeserializationHandlerInterface $handler): self;

    /**
     * @param int $direction one of the GraphNavigator::DIRECTION_??? constants
     *
     * @return callable|null
     */
    public function getHandler(int $direction, string $typeName): ?callable;
}
