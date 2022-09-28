<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use Symfony\Component\Yaml\Yaml;

class YamlDeserializationVisitor extends GenericDeserializationVisitor
{
    public function prepare(mixed $data): mixed
    {
        return Yaml::parse($data, Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);
    }
}
