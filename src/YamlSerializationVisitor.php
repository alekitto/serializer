<?php declare(strict_types=1);

namespace Kcs\Serializer;

use Symfony\Component\Yaml\Yaml;

/**
 * Serialization Visitor for the YAML format.
 *
 * @see http://www.yaml.org/spec/
 */
class YamlSerializationVisitor extends GenericSerializationVisitor
{
    /**
     * {@inheritdoc}
     */
    public function getResult()
    {
        $result = Yaml::dump($this->getRoot(), PHP_INT_MAX);
        if ("\n" !== \substr($result, -1)) {
            $result .= "\n";
        }

        return $result;
    }
}
