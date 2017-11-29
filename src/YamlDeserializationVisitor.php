<?php declare(strict_types=1);

namespace Kcs\Serializer;

use Symfony\Component\Yaml\Yaml;

class YamlDeserializationVisitor extends GenericDeserializationVisitor
{
    public function prepare($str)
    {
        return Yaml::parse($str, true);
    }

    public function getResult()
    {
        return $this->getRoot();
    }
}
