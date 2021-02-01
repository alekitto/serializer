<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader;

use Kcs\Serializer\Annotation;
use Kcs\Serializer\Metadata\ClassMetadata;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;

use function count;

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
        if (null !== $this->decorated && $this->decorated->isExcluded($class)) {
            return true;
        }

        return 0 !== count($class->getAttributes(Annotation\Exclude::class));
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
        if (null !== $this->decorated && $this->decorated->isPropertyExcluded($property, $classMetadata)) {
            return true;
        }

        if (Annotation\ExclusionPolicy::ALL === $classMetadata->exclusionPolicy) {
            return 0 === count($property->getAttributes(Annotation\Expose::class));
        }

        return 0 !== count($property->getAttributes(Annotation\Exclude::class));
    }
}
