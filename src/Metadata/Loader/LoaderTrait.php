<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader;

use Kcs\Serializer\Inflector\Inflector;
use ReflectionClass;

trait LoaderTrait
{
    private function createAnnotationObject(string $name)
    {
        switch ($className = Inflector::getInstance()->classify($name)) {
            case 'XmlList':
            case 'XmlNamespace':
                $className = 'Xml\\'.$className;
                break;

            default:
                if (0 === \strpos($className, 'Xml')) {
                    $className = 'Xml\\'.\substr($className, 3);
                }
                break;
        }

        $annotationClass = 'Kcs\\Serializer\\Annotation\\'.$className;
        $reflectionClass = new ReflectionClass($annotationClass);

        return $reflectionClass->newInstanceWithoutConstructor();
    }

    private function getDefaultPropertyName($annotation): ?string
    {
        $reflectionAnnotation = new ReflectionClass($annotation);
        $properties = $reflectionAnnotation->getProperties();

        return isset($properties[0]) ? $properties[0]->name : null;
    }

    private function convertValue(object $annotation, ?string $property, $value)
    {
        $reflectionProperty = new \ReflectionProperty($annotation, $property);
        $type = (string) $reflectionProperty->getType();
        switch ($type) {
            case 'int':
                $value = (int)$value;
                break;

            case '?array':
            case 'array':
                if (is_string($value)) {
                    $value = explode(',', $value);
                }
                break;

            case 'bool':
                $value = (bool)$value;
                break;

            case '?string':
            case 'string':
                if (is_bool($value)) {
                    $value = '';
                }
                break;

            case '':
                break;

            default:
                throw new \RuntimeException(\sprintf('Cannot convert mapping value %s to %s', \var_export($value, true), $type));
        }
        return $value;
    }
}
