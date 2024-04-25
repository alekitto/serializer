<?php

declare(strict_types=1);

namespace Kcs\Serializer\Handler;

use Kcs\Serializer\Direction;

/**
 * Handler Registry Interface.
 */
interface HandlerRegistryInterface
{
    public function registerSubscribingHandler(SubscribingHandlerInterface $handler): self;

    /**
     * Registers a handler in the registry.
     */
    public function registerHandler(Direction $direction, string $typeName, callable $handler): self;

    /**
     * Register a serialization handler.
     */
    public function registerSerializationHandler(SerializationHandlerInterface $handler): self;

    /**
     * Register a deserialization handler.
     */
    public function registerDeserializationHandler(DeserializationHandlerInterface $handler): self;

    public function getHandler(Direction $direction, string $typeName): callable|null;
}
