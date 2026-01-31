<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\Exception\InvalidArgumentException;

use function sprintf;
use function strtolower;

enum Direction
{
    case Deserialization;
    case Serialization;

    /**
     * Parses a direction string to one of the direction constants.
     */
    public static function parseDirection(string $dirStr): self
    {
        return match (strtolower($dirStr)) {
            'serialization' => self::Serialization,
            'deserialization' => self::Deserialization,
            default => throw new InvalidArgumentException(sprintf('The direction "%s" does not exist.', $dirStr)),
        };
    }

    public function toString(): string
    {
        return match ($this) {
            self::Deserialization => 'deserialization',
            self::Serialization => 'serialization',
        };
    }
}
