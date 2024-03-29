<?php

declare(strict_types=1);

namespace Kcs\Serializer\Handler;

/**
 * Represents a custom deserializer handler for the given type.
 */
interface DeserializationHandlerInterface
{
    /**
     * Gets the variable type handled by this handler.
     */
    public static function getType(): string;

    /**
     * Deserializes the data.
     */
    public function deserialize(mixed $data): mixed;
}
