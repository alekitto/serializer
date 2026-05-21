<?php

declare(strict_types=1);

namespace Kcs\Serializer\Serialization\Compiled;

final class CompiledPropertyDescriptor
{
    public function __construct(
        public readonly string $name,
        public readonly string $serializedName,
        public readonly string|null $nativeType,
        public readonly bool $inline,
    ) {
    }

    /** @param array{name: string, serializedName: string, nativeType: string|null, inline: bool} $data */
    public static function fromArray(array $data): self
    {
        return new self($data['name'], $data['serializedName'], $data['nativeType'], $data['inline']);
    }

    /** @return array{name: string, serializedName: string, nativeType: string|null, inline: bool} */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'serializedName' => $this->serializedName,
            'nativeType' => $this->nativeType,
            'inline' => $this->inline,
        ];
    }
}
