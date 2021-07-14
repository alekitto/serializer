<?php

declare(strict_types=1);

namespace Kcs\Serializer\Handler;

/**
 * Represents a custom serializer handler for the given type.
 */
interface SerializationHandlerInterface
{
    /**
     * Gets the variable type handled by this handler.
     */
    public static function getType(): string;

    /**
     * Serializes the data.
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function serialize($data);
}
