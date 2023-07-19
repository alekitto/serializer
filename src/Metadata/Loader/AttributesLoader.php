<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader;

use Kcs\Serializer\Annotation;
use Kcs\Serializer\Metadata\ClassMetadata;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

use function count;

class AttributesLoader extends AnnotationLoader
{
    private AnnotationLoader|null $decorated;

    public function __construct(AnnotationLoader|null $annotationLoader = null)
    {
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

    /** @return object[] */
    protected function getClassAnnotations(ClassMetadata $classMetadata): array
    {
        $attributes = $this->decorated ? $this->decorated->getClassAnnotations($classMetadata) : [];
        foreach ($classMetadata->getReflectionClass()->getAttributes() as $attribute) {
            $attributes[] = $attribute->newInstance();
        }

        return $attributes;
    }

    /** @return object[] */
    protected function getMethodAnnotations(ReflectionMethod $method): array
    {
        $attributes = $this->decorated ? $this->decorated->getMethodAnnotations($method) : [];
        foreach ($method->getAttributes() as $attribute) {
            $attributes[] = $attribute->newInstance();
        }

        return $attributes;
    }

    /** @return object[] */
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
