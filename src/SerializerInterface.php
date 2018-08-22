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
     * @return string
     */
    public function serialize($data, $format, SerializationContext $context = null, Type $type = null);

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
    public function deserialize($data, Type $type, $format, DeserializationContext $context = null);
}
