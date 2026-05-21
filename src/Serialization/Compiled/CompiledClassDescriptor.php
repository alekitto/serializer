<?php

declare(strict_types=1);

namespace Kcs\Serializer\Serialization\Compiled;

use function array_map;
use function array_values;

final class CompiledClassDescriptor
{
    /** @param CompiledPropertyDescriptor[] $properties */
    public function __construct(
        public readonly string $className,
        public readonly string $namingStrategy,
        public readonly array $properties,
    ) {
    }

    /** @param array{className: string, namingStrategy: string, properties: list<array{name: string, serializedName: string, nativeType: string|null, inline: bool}>} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['className'],
            $data['namingStrategy'],
            array_map(static fn (array $property): CompiledPropertyDescriptor => CompiledPropertyDescriptor::fromArray($property), $data['properties']),
        );
    }

    /**
     * @return array{
     *     className: string,
     *     namingStrategy: string,
     *     properties: list<array{name: string, serializedName: string, nativeType: string|null, inline: bool}>
     * }
     */
    public function toArray(): array
    {
        return [
            'className' => $this->className,
            'namingStrategy' => $this->namingStrategy,
            'properties' => array_values(array_map(
                static fn (CompiledPropertyDescriptor $property): array => $property->toArray(),
                $this->properties,
            )),
        ];
    }
}
