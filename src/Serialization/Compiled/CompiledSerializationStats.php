<?php

declare(strict_types=1);

namespace Kcs\Serializer\Serialization\Compiled;

final class CompiledSerializationStats
{
    /**
     * @param array<string, int> $delegationReasons
     * @param array<string, int> $iterableFastPathReasons
     * @param array<string, int> $skippedNullReasons
     */
    public function __construct(
        public readonly int $compiledObjects,
        public readonly int $fallbackObjects,
        public readonly int $delegatedProperties = 0,
        public readonly array $delegationReasons = [],
        public readonly int $iterableFastPathProperties = 0,
        public readonly array $iterableFastPathReasons = [],
        public readonly int $skippedNullProperties = 0,
        public readonly array $skippedNullReasons = [],
    ) {
    }
}
