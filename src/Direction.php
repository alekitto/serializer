<?php declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\Exception\InvalidArgumentException;

class Direction
{
    const DIRECTION_DESERIALIZATION = 2;
    const DIRECTION_SERIALIZATION = 1;

    /**
     * Parses a direction string to one of the direction constants.
     *
     * @param string $dirStr
     *
     * @return int
     */
    public static function parseDirection(string $dirStr): int
    {
        switch (\strtolower($dirStr)) {
            case 'serialization':
                return self::DIRECTION_SERIALIZATION;

            case 'deserialization':
                return self::DIRECTION_DESERIALIZATION;

            default:
                throw new InvalidArgumentException(\sprintf('The direction "%s" does not exist.', $dirStr));
        }
    }
}
