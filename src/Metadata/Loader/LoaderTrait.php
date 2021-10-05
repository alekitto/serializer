<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader;

use Kcs\Serializer\Inflector\Inflector;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use RuntimeException;

use function explode;
use function is_bool;
use function is_string;
use function Safe\sprintf;
use function Safe\substr;
use function strpos;
use function var_export;

trait LoaderTrait
{
    private function createAnnotationObject(string $name): object
    {
        switch ($className = Inflector::getInstance()->classify($name)) {
            case 'XmlList':
            case 'XmlNamespace':
                $className = 'Xml\\' . $className;
                break;

            default:
                if (strpos($className, 'Xml') === 0) {
                    $className = 'Xml\\' . substr($className, 3);
                }

                break;
        }

        /** @phpstan-var class-string $annotationClass */
        $annotationClass = 'Kcs\\Serializer\\Annotation\\' . $className;
        $reflectionClass = new ReflectionClass($annotationClass);

        return $reflectionClass->newInstanceWithoutConstructor();
    }

    private function getDefaultPropertyName(object $annotation): ?string
    {
        $reflectionAnnotation = new ReflectionClass($annotation);
        $properties = $reflectionAnnotation->getProperties();

        return isset($properties[0]) ? $properties[0]->name : null;
    }

    private function convertValue(object $annotation, string $property, mixed $value): mixed
    {
        $reflectionProperty = new ReflectionProperty($annotation, $property);
        $type = $reflectionProperty->getType();
        if ($type instanceof ReflectionUnionType) {
            return $value;
        }

        $type = $type instanceof ReflectionNamedType ? $type->getName() : (string) $type;
        switch ($type) {
            case 'int':
                $value = (int) $value;
                break;

            case '?array':
            case 'array':
                if (is_string($value)) {
                    $value = explode(',', $value);
                }

                break;

            case 'bool':
                $value = (bool) $value;
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
                throw new RuntimeException(sprintf('Cannot convert mapping value %s to %s', var_export($value, true), $type));
        }

        return $value;
    }
}
