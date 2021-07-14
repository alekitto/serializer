<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader;

use Kcs\Serializer\Annotation;
use Kcs\Serializer\Metadata\ClassMetadata;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;

use function count;

use const PHP_VERSION_ID;

class AttributesLoader extends AnnotationLoader
{
    private ?AnnotationLoader $decorated;

    public function __construct(?AnnotationLoader $annotationLoader = null)
    {
        if (PHP_VERSION_ID < 80000) {
            throw new RuntimeException('Attributes loader can only be used with PHP >= 8.0');
        }

        parent::__construct();

        $this->decorated = $annotationLoader;
    }

    protected function isExcluded(ReflectionClass $class): bool
    {
        if ($this->decorated !== null && $this->decorated->isExcluded($class)) {
            return true;
        }

        return count($class->getAttributes(Annotation\Exclude::class)) !== 0;
    }

    protected function getClassAnnotations(ClassMetadata $classMetadata): array
    {
        $attributes = $this->decorated ? $this->decorated->getClassAnnotations($classMetadata) : [];
        foreach ($classMetadata->getReflectionClass()->getAttributes() as $attribute) {
            $attributes[] = $attribute->newInstance();
        }

        return $attributes;
    }

    protected function getMethodAnnotations(ReflectionMethod $method): array
    {
        $attributes = $this->decorated ? $this->decorated->getMethodAnnotations($method) : [];
        foreach ($method->getAttributes() as $attribute) {
            $attributes[] = $attribute->newInstance();
        }

        return $attributes;
    }

    protected function getPropertyAnnotations(ReflectionProperty $property): array
    {
        $attributes = $this->decorated ? $this->decorated->getPropertyAnnotations($property) : [];
        foreach ($property->getAttributes() as $attribute) {
            $attributes[] = $attribute->newInstance();
        }

        return $attributes;
    }

    protected function isPropertyExcluded(ReflectionProperty $property, ClassMetadata $classMetadata): bool
    {
        if ($this->decorated !== null && $this->decorated->isPropertyExcluded($property, $classMetadata)) {
            return true;
        }

        if ($classMetadata->exclusionPolicy === Annotation\ExclusionPolicy::ALL) {
            return count($property->getAttributes(Annotation\Expose::class)) === 0;
        }

        return count($property->getAttributes(Annotation\Exclude::class)) !== 0;
    }
}
