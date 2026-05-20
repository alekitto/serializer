<?php

declare(strict_types=1);

namespace Kcs\Serializer\Serialization\Compiled;

final class CompiledSerializationStats
{
    /** @param array<string, int> $delegationReasons */
    public function __construct(
        public readonly int $compiledObjects,
        public readonly int $fallbackObjects,
        public readonly int $delegatedProperties = 0,
        public readonly array $delegationReasons = [],
    ) {
    }
}
