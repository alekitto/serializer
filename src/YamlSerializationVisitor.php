<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use Symfony\Component\Yaml\Yaml;

use function Safe\substr;

use const PHP_INT_MAX;

/**
 * Serialization Visitor for the YAML format.
 *
 * @see http://www.yaml.org/spec/
 */
class YamlSerializationVisitor extends GenericSerializationVisitor
{
    public function getResult(): string
    {
        $result = Yaml::dump($this->getRoot(), PHP_INT_MAX);
        if (substr($result, -1) !== "\n") {
            $result .= "\n";
        }

        return $result;
    }
}
