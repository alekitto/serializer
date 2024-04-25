<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader;

use Kcs\Metadata\ClassMetadataInterface;
use Kcs\Metadata\Loader\LoaderInterface;
use Kcs\Serializer\Attribute;
use Kcs\Serializer\Metadata\AdditionalPropertyMetadata;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\Exclusion;
use Kcs\Serializer\Metadata\Loader\Processor\AnnotationProcessor;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Metadata\StaticPropertyMetadata;
use Kcs\Serializer\Metadata\VirtualPropertyMetadata;
use LogicException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

use function count;

class AttributesLoader implements LoaderInterface
{
    private AnnotationProcessor $processor;

    public function __construct()
    {
        $this->processor = new AnnotationProcessor();
    }

    public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool
    {
        if (! $classMetadata instanceof ClassMetadata) {
            throw new LogicException('wrong metadata class');
        }

        $class = $classMetadata->getReflectionClass();
        if ($this->isExcluded($class)) {
            return true;
        }

        $this->processClassAnnotations($classMetadata);

        foreach ($class->getMethods() as $method) {
            if ($method->getDeclaringClass()->name !== $class->name) {
                continue;
            }

            $this->processMethodAnnotations($method, $classMetadata);
        }

        foreach ($class->getProperties() as $property) {
            if ($property->getDeclaringClass()->name !== $class->name) {
                continue;
            }

            $this->processPropertyAnnotations($property, $classMetadata);
        }

        return true;
    }

    protected function isExcluded(ReflectionClass $class): bool
    {
        return count($class->getAttributes(Attribute\Exclude::class)) !== 0;
    }

    /** @return object[] */
    protected function getClassAnnotations(ClassMetadata $classMetadata): array
    {
        $attributes = [];
        foreach ($classMetadata->getReflectionClass()->getAttributes() as $attribute) {
            $attributes[] = $attribute->newInstance();
        }

        return $attributes;
    }

    /** @return object[] */
    protected function getMethodAnnotations(ReflectionMethod $method): array
    {
        $attributes = [];
        foreach ($method->getAttributes() as $attribute) {
            $attributes[] = $attribute->newInstance();
        }

        return $attributes;
    }

    /** @return object[] */
    protected function getPropertyAnnotations(ReflectionProperty $property): array
    {
        $attributes = [];
        foreach ($property->getAttributes() as $attribute) {
            $attributes[] = $attribute->newInstance();
        }

        return $attributes;
    }

    protected function isPropertyExcluded(ReflectionProperty $property, ClassMetadata $classMetadata): bool
    {
        if ($classMetadata->exclusionPolicy === Exclusion\Policy::All) {
            return count($property->getAttributes(Attribute\Expose::class)) === 0;
        }

        return count($property->getAttributes(Attribute\Exclude::class)) !== 0;
    }

    private function processClassAnnotations(ClassMetadata $classMetadata): void
    {
        $annotations = $this->getClassAnnotations($classMetadata);
        foreach ($annotations as $annotation) {
            $this->processor->process($annotation, $classMetadata);

            if ($annotation instanceof Attribute\AdditionalField) {
                $additionalMetadata = new AdditionalPropertyMetadata($classMetadata->name, $annotation->name);
                $this->loadExposedAttribute($additionalMetadata, $annotation->attributes, $classMetadata);
            } elseif ($annotation instanceof Attribute\StaticField) {
                $staticMetadata = new StaticPropertyMetadata($classMetadata->name, $annotation->name, $annotation->value);
                $this->loadExposedAttribute($staticMetadata, $annotation->attributes, $classMetadata);
            }
        }
    }

    private function processMethodAnnotations(ReflectionMethod $method, ClassMetadata $classMetadata): void
    {
        /** @phpstan-var class-string $class */
        $class = $method->class;

        $methodAnnotations = $this->getMethodAnnotations($method);
        foreach ($methodAnnotations as $annotation) {
            if (! ($annotation instanceof Attribute\VirtualProperty)) {
                continue;
            }

            $virtualPropertyMetadata = new VirtualPropertyMetadata($class, $method->name);
            $this->loadExposedAttribute($virtualPropertyMetadata, $methodAnnotations, $classMetadata);
        }
    }

    private function processPropertyAnnotations(ReflectionProperty $property, ClassMetadata $classMetadata): void
    {
        /** @phpstan-var class-string $class */
        $class = $property->class;

        if ($this->isPropertyExcluded($property, $classMetadata)) {
            return;
        }

        $metadata = new PropertyMetadata($class, $property->name);
        $annotations = $this->getPropertyAnnotations($property);
        $this->loadExposedAttribute($metadata, $annotations, $classMetadata);
    }

    /** @param object[] $annotations */
    private function loadExposedAttribute(PropertyMetadata $metadata, array $annotations, ClassMetadata $classMetadata): void
    {
        $metadata->immutable = $metadata->immutable || $classMetadata->immutable;
        $accessType = $classMetadata->defaultAccessType;

        $accessor = [null, null];
        foreach ($annotations as $annotation) {
            $this->processor->process($annotation, $metadata);

            if ($annotation instanceof Attribute\AccessType) {
                $accessType = $annotation->type;
            } elseif ($annotation instanceof Attribute\Accessor) {
                $accessor = [$annotation->getter, $annotation->setter];
            }
        }

        $metadata->setAccessor($accessType, $accessor[0], $accessor[1]);
        $classMetadata->addAttributeMetadata($metadata);
    }
}
