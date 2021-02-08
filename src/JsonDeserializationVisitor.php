<?php declare(strict_types=1);

namespace Kcs\Serializer;

use JsonException;
use Kcs\Serializer\Exception\RuntimeException;

class JsonDeserializationVisitor extends GenericDeserializationVisitor
{
    /**
     * {@inheritdoc}
     */
    public function prepare($str)
    {
        try {
            return \json_decode($str, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Could not decode JSON: '.$exception->getMessage(), 0, $exception);
        }
    }
}
