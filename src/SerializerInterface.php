<?php declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\Type\Type;

interface SerializerInterface
{
    /**
     * Serializes the given data to the specified output format.
     *
     * @param mixed                $data
     * @param string               $format
     * @param SerializationContext $context
     * @param Type|null            $type
     *
     * @return mixed
     */
    public function serialize($data, string $format, ?SerializationContext $context = null, ?Type $type = null);

    /**
     * Deserializes the given data to the specified type.
     *
     * @param string|mixed           $data
     * @param Type                   $type
     * @param string                 $format
     * @param DeserializationContext $context
     *
     * @return mixed
     */
    public function deserialize($data, Type $type, string $format, ?DeserializationContext $context = null);

    /**
     * Converts objects to an array structure.
     *
     * This is useful when the data needs to be passed on to other methods which expect array data.
     *
     * @param mixed                $data    anything that converts to an array, typically an object or an array of objects
     * @param SerializationContext $context
     *
     * @return array
     */
    public function normalize($data, SerializationContext $context = null): array;

    /**
     * Restores objects from an array structure.
     *
     * @param array                  $data
     * @param Type                   $type
     * @param DeserializationContext $context
     *
     * @return mixed this returns whatever the passed type is, typically an object or an array of objects
     */
    public function denormalize(array $data, Type $type, DeserializationContext $context = null);
}
