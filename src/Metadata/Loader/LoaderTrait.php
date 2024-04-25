<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader;

use Kcs\Serializer\Inflector\Inflector;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use RuntimeException;
use UnitEnum;

use function explode;
use function is_bool;
use function is_string;
use function is_subclass_of;
use function mb_convert_case;
use function mb_strtolower;
use function preg_replace;
use function preg_replace_callback;
use function sprintf;
use function str_replace;
use function strpos;
use function substr;
use function var_export;

use const MB_CASE_TITLE;

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
        $annotationClass = 'Kcs\\Serializer\\Attribute\\' . $className;
        $reflectionClass = new ReflectionClass($annotationClass);

        $instance = $reflectionClass->newInstanceWithoutConstructor();

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if (! $reflectionProperty->hasDefaultValue()) {
                continue;
            }

            $reflectionProperty->setValue($instance, $reflectionProperty->getDefaultValue());
        }

        foreach ($reflectionClass->getConstructor()?->getParameters() ?? [] as $reflectionParameter) {
            if (! $reflectionParameter->isPromoted() || ! $reflectionParameter->isOptional()) {
                continue;
            }

            $reflectionClass->getProperty($reflectionParameter->getName())->setValue($instance, $reflectionParameter->getDefaultValue());
        }

        return $instance;
    }

    private function getDefaultPropertyName(object $annotation): string|null
    {
        $reflectionAnnotation = new ReflectionClass($annotation);
        $properties = $reflectionAnnotation->getProperties();

        return isset($properties[0]) ? $properties[0]->name : null;
    }

    private static function pascalCase(string $str): string
    {
        /** @phpstan-ignore-next-line */
        return str_replace(' ', '', preg_replace_callback(
            '/\b.(?![A-Z]{2,})/u',
            static fn ($m) => mb_convert_case($m[0], MB_CASE_TITLE, 'UTF-8'),
            preg_replace('/[^\pL0-9]++/u', ' ', mb_strtolower($str)), /** @phpstan-ignore-line */
        ));
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
                if (! is_string($value) || ! is_subclass_of($type, UnitEnum::class)) {
                    throw new RuntimeException(sprintf('Cannot convert mapping value %s to %s', var_export($value, true), $type));
                }

                $caseValue = self::pascalCase($value);
                foreach ($type::cases() as $case) {
                    if ($case->name === $caseValue) {
                        $value = $case;
                        break;
                    }
                }
        }

        return $value;
    }
}
