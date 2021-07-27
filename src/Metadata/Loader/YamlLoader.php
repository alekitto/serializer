<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader;

use Kcs\Metadata\ClassMetadataInterface;
use Kcs\Metadata\Loader\FileLoaderTrait;
use Kcs\Serializer\Annotation as Annotations;
use Kcs\Serializer\Metadata\ClassMetadata;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Yaml\Yaml;

use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_push;
use function array_values;
use function in_array;
use function is_array;

class YamlLoader extends AnnotationLoader
{
    use FileLoaderTrait;
    use LoaderTrait;

    /** @var array<mixed, mixed> */
    private array $config;

    public function __construct(string $filePath)
    {
        parent::__construct();

        $this->config = (array) Yaml::parse($this->loadFile($filePath));
    }

    protected function isPropertyExcluded(ReflectionProperty $property, ClassMetadata $classMetadata): bool
    {
        $config = $this->getClassConfig($classMetadata->getName());
        if ($classMetadata->exclusionPolicy === Annotations\ExclusionPolicy::ALL) {
            if (array_key_exists($property->name, $config['properties']) && $config['properties'][$property->name] === null) {
                return false;
            }

            return ! isset($config['properties'][$property->name]['expose']) || ! $config['properties'][$property->name]['expose'];
        }

        return isset($config['properties'][$property->name]['exclude']) && $config['properties'][$property->name]['exclude'];
    }

    public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool
    {
        if (! $this->hasClassConfig($classMetadata->getName())) {
            return true;
        }

        return parent::loadClassMetadata($classMetadata);
    }

    protected function isExcluded(ReflectionClass $class): bool
    {
        $config = $this->getClassConfig($class->name);

        return isset($config['exclude']) && $config['exclude'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getClassAnnotations(ClassMetadata $classMetadata): array
    {
        $config = $this->getClassConfig($classMetadata->getName());

        $annotations = [];
        foreach ($config as $key => $value) {
            if ($key === 'static_fields') {
                foreach ($value as $property => $item) {
                    if (! is_array($item)) {
                        $item = ['value' => $item];
                    }

                    $value = $item['value'];
                    unset($item['value']);

                    $annotation = new Annotations\StaticField($property, $this->loadProperty($item), $value);
                    $annotations[] = $annotation;
                }

                continue;
            }

            if ($key === 'additional_fields') {
                foreach ($value as $property => $item) {
                    $annotation = new Annotations\AdditionalField($property, $this->loadProperty($item));
                    $annotations[] = $annotation;
                }

                continue;
            }

            if (in_array($key, ['properties', 'virtual_properties'])) {
                continue;
            }

            array_push($annotations, ...$this->createAnnotationsForArray($value, $key));
        }

        return $annotations;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMethodAnnotations(ReflectionMethod $method): array
    {
        $annotations = [];
        $methodName = $method->name;

        // @phpstan-ignore-next-line
        $config = $this->getClassConfig($method->class);

        if (array_key_exists($methodName, $config['virtual_properties'])) {
            $annotations[] = new Annotations\VirtualProperty();

            $methodConfig = $config['virtual_properties'][$methodName] ?: [];
            array_push($annotations, ...$this->loadProperty($methodConfig));
        }

        return $annotations;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPropertyAnnotations(ReflectionProperty $property): array
    {
        // @phpstan-ignore-next-line
        $config = $this->getClassConfig($property->class);
        $propertyName = $property->name;

        if (! isset($config['properties'][$propertyName])) {
            return [];
        }

        return $this->loadProperty($config['properties'][$propertyName]);
    }

    /**
     * Whether the passed array is associative or not.
     *
     * @param array<string|int, mixed> $value
     */
    private static function isAssocArray(array $value): bool
    {
        return array_keys($value) !== array_keys(array_values($value));
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return object[]
     */
    private function loadProperty(array $config): array
    {
        $annotations = [];

        foreach ($config as $key => $value) {
            array_push($annotations, ...$this->createAnnotationsForArray($value, $key));
        }

        return $annotations;
    }

    /**
     * @phpstan-param class-string $class
     */
    private function hasClassConfig(string $class): bool
    {
        return isset($this->config[$class]);
    }

    /**
     * @phpstan-param class-string $class
     *
     * @return array<string, mixed>
     */
    private function getClassConfig(string $class): array
    {
        return array_merge([
            'virtual_properties' => [],
        ], $this->config[$class] ?? []);
    }

    /**
     * @param array<string, mixed>|mixed[]|mixed $value
     *
     * @return object[]
     */
    private function createAnnotationsForArray($value, string $key): array
    {
        $annotations = [];

        if (! is_array($value)) {
            $annotation = $this->createAnnotationObject($key);
            $property = $this->getDefaultPropertyName($annotation);
            if (! empty($property)) {
                $annotation->{$property} = $this->convertValue($annotation, $property, $value);
            }

            $annotations[] = $annotation;
        } elseif (self::isAssocArray($value)) {
            $annotation = $this->createAnnotationObject($key);
            foreach ($value as $property => $val) {
                $annotation->{$property} = $this->convertValue($annotation, $property, $val);
            }

            $annotations[] = $annotation;
        } elseif ($key === 'groups') {
            $annotations[] = new Annotations\Groups($value);
        } else {
            foreach ($value as $annotValue) {
                array_push($annotations, ...$this->createAnnotationsForArray($annotValue, $key));
            }
        }

        return $annotations;
    }
}
