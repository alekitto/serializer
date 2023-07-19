<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\Type\Type;

interface SerializerInterface
{
    /**
     * Serializes the given data to the specified output format.
     */
    public function serialize(mixed $data, string $format, SerializationContext|null $context = null, Type|null $type = null): mixed;

    /**
     * Deserializes the given data to the specified type.
     */
    public function deserialize(mixed $data, Type $type, string $format, DeserializationContext|null $context = null): mixed;

    /**
     * Converts objects to an array structure.
     *
     * This is useful when the data needs to be passed on to other methods which expect array data.
     *
     * @param mixed $data anything that converts to an array, typically an object or an array of objects
     *
     * @return array<mixed, mixed>
     */
    public function normalize(mixed $data, SerializationContext|null $context = null): array;

    /**
     * Restores objects from an array structure.
     *
     * @param array<mixed, mixed> $data
     *
     * @return mixed this returns whatever the passed type is, typically an object or an array of objects
     */
    public function denormalize(array $data, Type $type, DeserializationContext|null $context = null): mixed;
}
