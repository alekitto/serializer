<?php

declare(strict_types=1);

namespace Kcs\Serializer\Serialization\Compiled;

final class CompiledClassPlan
{
    /** @param CompiledPropertyPlan[] $properties */
    public function __construct(
        public readonly array $properties,
        public readonly bool $nativeOnly,
    ) {
    }
}
