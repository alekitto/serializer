<?php declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\Type\Type;

/**
 * Serializer Interface.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface SerializerInterface
{
    /**
     * Serializes the given data to the specified output format.
     *
     * @param mixed                $data
     * @param string               $format
     * @param SerializationContext $context
     *
     * @return string
     */
    public function serialize($data, $format, SerializationContext $context = null);

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
