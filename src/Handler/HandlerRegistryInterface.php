<?php

declare(strict_types=1);

namespace Kcs\Serializer\Handler;

/**
 * Handler Registry Interface.
 */
interface HandlerRegistryInterface
{
    public function registerSubscribingHandler(SubscribingHandlerInterface $handler): self;

    /**
     * Registers a handler in the registry.
     *
     * @param int      $direction one of the GraphNavigator::DIRECTION_??? constants
     * @param callable $handler   function(VisitorInterface, mixed $data, array $type): mixed
     *
     * @return $this
     */
    public function registerHandler(int $direction, string $typeName, callable $handler): self;

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
     */
    public function getHandler(int $direction, string $typeName): ?callable;
}
