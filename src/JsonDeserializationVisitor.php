<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use JsonException;
use Kcs\Serializer\Exception\RuntimeException;

use function json_decode;

use const JSON_THROW_ON_ERROR;

class JsonDeserializationVisitor extends GenericDeserializationVisitor
{
    public function prepare(mixed $data): mixed
    {
        try {
            return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Could not decode JSON: ' . $exception->getMessage(), 0, $exception);
        }
    }
}
