<?php declare(strict_types=1);

namespace Kcs\Serializer;

use Symfony\Component\Yaml\Yaml;

class YamlDeserializationVisitor extends GenericDeserializationVisitor
{
    /**
     * {@inheritdoc}
     */
    public function prepare($str)
    {
        return Yaml::parse($str, Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);
    }
}
