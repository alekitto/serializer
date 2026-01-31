<?php

declare(strict_types=1);

namespace Kcs\Serializer\Handler;

use Kcs\Serializer\Direction;

interface SubscribingHandlerInterface
{
    /**
     * Return format:.
     *
     *      yield [
     *          'direction' => Direction::Serialization,
     *          'type' => 'DateTime',
     *          'method' => 'serializeDateTimeToJson',
     *      ];
     *
     * The direction and method keys can be omitted.
     *
     * @return iterable<array<string, mixed>>
     * @phpstan-return iterable<array{direction: Direction, type: string, method: string}>
     */
    public static function getSubscribingMethods(): iterable;
}
