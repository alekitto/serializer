<?php

declare(strict_types=1);

namespace Kcs\Serializer\Serialization\Compiled;

interface CompiledSerializationDescriptorCacheInterface
{
    public function get(string $key): CompiledClassDescriptor|null;

    public function save(string $key, CompiledClassDescriptor $descriptor): void;
}
