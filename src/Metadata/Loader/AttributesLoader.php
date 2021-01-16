<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader;

use Kcs\Serializer\Annotation;
use Kcs\Serializer\Metadata\ClassMetadata;

class AttributesLoader extends AnnotationLoader
{
    public function __construct()
    {
        if (PHP_VERSION_ID < 80000) {
            throw new \RuntimeException('Attributes loader can only be used with PHP >= 8.0');
        }

        parent::__construct();
    }

    protected function isExcluded(\ReflectionClass $class): bool
    {
        return count($class->getAttributes(Annotation\Exclude::class)) !== 0;
    }

    protected function getClassAnnotations(ClassMetadata $classMetadata): array
    {
        $attributes = [];
        foreach ($classMetadata->getReflectionClass()->getAttributes() as $attribute) {
            $attributes[] = $attribute->newInstance();
        }

        return $attributes;
    }

    protected function getMethodAnnotations(\ReflectionMethod $method): array
    {
        $attributes = [];
        foreach ($method->getAttributes() as $attribute) {
            $attributes[] = $attribute->newInstance();
        }

        return $attributes;
    }

    protected function getPropertyAnnotations(\ReflectionProperty $property): array
    {
        $attributes = [];
        foreach ($property->getAttributes() as $attribute) {
            $attributes[] = $attribute->newInstance();
        }

        return $attributes;
    }

    protected function isPropertyExcluded(\ReflectionProperty $property, ClassMetadata $classMetadata): bool
    {
        if (Annotation\ExclusionPolicy::ALL === $classMetadata->exclusionPolicy) {
            return 0 === count($property->getAttributes(Annotation\Expose::class));
        }

        return 0 !== count($property->getAttributes(Annotation\Exclude::class));
    }
}
