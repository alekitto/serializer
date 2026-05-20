<?php

declare(strict_types=1);

namespace Kcs\Serializer\Serialization\Compiled;

use Closure;
use Kcs\Serializer\Metadata\PropertyMetadata;

final class CompiledPropertyPlan
{
    /** @param Closure(object): mixed|null $reader */
    public function __construct(
        public readonly PropertyMetadata $metadata,
        public readonly string $serializedName,
        public readonly string|null $nativeType,
        private readonly Closure|null $reader,
    ) {
    }

    public function read(object $object): mixed
    {
        if ($this->reader !== null) {
            return ($this->reader)($object);
        }

        return $this->metadata->getValue($object);
    }
}
