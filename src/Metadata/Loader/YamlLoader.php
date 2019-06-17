<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader;

use Kcs\Metadata\ClassMetadataInterface;
use Kcs\Metadata\Loader\FileLoaderTrait;
use Kcs\Serializer\Annotation as Annotations;
use Kcs\Serializer\Metadata\ClassMetadata;
use Symfony\Component\Yaml\Yaml;

class YamlLoader extends AnnotationLoader
{
    use FileLoaderTrait;
    use LoaderTrait;

    /**
     * @var array
     */
    private $config;

    public function __construct(string $filePath)
    {
        parent::__construct();

        $this->config = (array) Yaml::parse($this->loadFile($filePath));
    }

    /**
     * {@inheritdoc}
     */
    protected function isPropertyExcluded(\ReflectionProperty $property, ClassMetadata $classMetadata): bool
    {
        $config = $this->getClassConfig($classMetadata->getName());
        if (Annotations\ExclusionPolicy::ALL === $classMetadata->exclusionPolicy) {
            if (\array_key_exists($property->name, $config['properties']) && null === $config['properties'][$property->name]) {
                return false;
            }

            return ! isset($config['properties'][$property->name]['expose']) || ! $config['properties'][$property->name]['expose'];
        }

        return isset($config['properties'][$property->name]['exclude']) && $config['properties'][$property->name]['exclude'];
    }

    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool
    {
        if (! $this->hasClassConfig($classMetadata->getName())) {
            return true;
        }

        return parent::loadClassMetadata($classMetadata);
    }

    /**
     * {@inheritdoc}
     */
    protected function isExcluded(\ReflectionClass $class): bool
    {
        $config = $this->getClassConfig($class->name);

        return isset($config['exclude']) ? (bool) $config['exclude'] : false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getClassAnnotations(ClassMetadata $classMetadata): array
    {
        $config = $this->getClassConfig($classMetadata->getName());

        $annotations = [];
        foreach ($config as $key => $value) {
            if ('static_fields' === $key) {
                foreach ($value as $property => $item) {
                    if (! \is_array($item)) {
                        $item = ['value' => $item];
                    }

                    $annotation = new Annotations\StaticField();
                    $annotation->name = $property;
                    $annotation->value = $item['value'];
                    unset($item['value']);

                    $annotation->attributes = $this->loadProperty($item);
                    $annotations[] = $annotation;
                }

                continue;
            }

            if (\in_array($key, ['properties', 'virtual_properties'])) {
                continue;
            }

            $annotations = \array_merge($annotations, $this->createAnnotationsForArray($value, $key));
        }

        return $annotations;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMethodAnnotations(\ReflectionMethod $method): array
    {
        $annotations = [];
        $methodName = $method->name;
        $config = $this->getClassConfig($method->class);

        if (\array_key_exists($methodName, $config['virtual_properties'])) {
            $annotations[] = new Annotations\VirtualProperty();

            $methodConfig = $config['virtual_properties'][$methodName] ?: [];
            $annotations = \array_merge($annotations, $this->loadProperty($methodConfig));
        }

        return $annotations;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPropertyAnnotations(\ReflectionProperty $property): array
    {
        $config = $this->getClassConfig($property->class);
        $propertyName = $property->name;

        if (! isset($config['properties'][$propertyName])) {
            return [];
        }

        return $this->loadProperty($config['properties'][$propertyName]);
    }

    /**
     * {@inheritdoc}
     */
    private static function isAssocArray(array $value): bool
    {
        return \array_keys($value) !== \array_keys(\array_values($value));
    }

    private function loadProperty(array $config): array
    {
        $annotations = [];

        foreach ($config as $key => $value) {
            $annotations = \array_merge($annotations, $this->createAnnotationsForArray($value, $key));
        }

        return $annotations;
    }

    private function hasClassConfig($class): bool
    {
        return isset($this->config[$class]);
    }

    private function getClassConfig($class): array
    {
        return \array_merge([
            'virtual_properties' => [],
        ], $this->config[$class] ?? []);
    }

    private function createAnnotationsForArray($value, string $key): array
    {
        $annotations = [];

        if (! \is_array($value)) {
            $annotation = $this->createAnnotationObject($key);
            if ($property = $this->getDefaultPropertyName($annotation)) {
                $annotation->{$property} = $value;
            }

            $annotations[] = $annotation;
        } elseif (self::isAssocArray($value)) {
            $annotation = $this->createAnnotationObject($key);
            foreach ($value as $property => $val) {
                $annotation->{$property} = $val;
            }

            $annotations[] = $annotation;
        } elseif ('groups' === $key) {
            $annotation = new Annotations\Groups();
            $annotation->groups = $value;

            $annotations[] = $annotation;
        } else {
            foreach ($value as $annotValue) {
                $annotations = \array_merge($annotations, $this->createAnnotationsForArray($annotValue, $key));
            }
        }

        return $annotations;
    }
}
