<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 * Modifications copyright (c) 2016 Alessandro Chitolina <alekitto@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Kcs\Serializer\Metadata\Loader;

use Doctrine\Common\Annotations\Reader;
use Kcs\Metadata\ClassMetadataInterface;
use Kcs\Metadata\Loader\LoaderInterface;
use Kcs\Metadata\MethodMetadata;
use Kcs\Serializer\Annotation;
use Kcs\Serializer\Metadata\AdditionalPropertyMetadata;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\Loader\Processor\AnnotationProcessor;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Metadata\VirtualPropertyMetadata;

class AnnotationLoader implements LoaderInterface
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var AnnotationProcessor
     */
    private $processor;

    public function __construct()
    {
        $this->processor = new AnnotationProcessor();
    }

    /**
     * @param Reader $reader
     */
    public function setReader(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function loadClassMetadata(ClassMetadataInterface $classMetadata)
    {
        if (! $classMetadata instanceof ClassMetadata) {
            throw new \LogicException('wrong metadata class');
        }

        /** @var ClassMetadata $classMetadata */
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

    /**
     * @param ClassMetadata $classMetadata
     */
    private function processClassAnnotations(ClassMetadata $classMetadata)
    {
        $annotations = $this->getClassAnnotations($classMetadata);
        foreach ($annotations as $annotation) {
            $this->processor->process($annotation, $classMetadata);

            if ($annotation instanceof Annotation\AdditionalField) {
                $additionalMetadata = new AdditionalPropertyMetadata($classMetadata->name, $annotation->name);
                $this->loadExposedAttribute($additionalMetadata, $annotation->attributes, $classMetadata);
            }
        }
    }

    private function processMethodAnnotations(\ReflectionMethod $method, ClassMetadata $classMetadata)
    {
        $class = $method->class;

        $methodAnnotations = $this->getMethodAnnotations($method);
        foreach ($methodAnnotations as $annotation) {
            switch (true) {
                case $annotation instanceof Annotation\PreSerialize:
                    $classMetadata->addPreSerializeMethod(new MethodMetadata($class, $method->name));
                    break;

                case $annotation instanceof Annotation\PostDeserialize:
                    $classMetadata->addPostDeserializeMethod(new MethodMetadata($class, $method->name));
                    break;

                case $annotation instanceof Annotation\PostSerialize:
                    $classMetadata->addPostSerializeMethod(new MethodMetadata($class, $method->name));
                    break;

                case $annotation instanceof Annotation\VirtualProperty:
                    $virtualPropertyMetadata = new VirtualPropertyMetadata($class, $method->name);
                    $this->loadExposedAttribute($virtualPropertyMetadata, $methodAnnotations, $classMetadata);
                    break;
            }
        }
    }

    private function processPropertyAnnotations(\ReflectionProperty $property, ClassMetadata $classMetadata)
    {
        $class = $property->class;

        if ($this->isPropertyExcluded($property, $classMetadata)) {
            return;
        }

        $metadata = new PropertyMetadata($class, $property->name);
        $annotations = $this->getPropertyAnnotations($property);
        $this->loadExposedAttribute($metadata, $annotations, $classMetadata);
    }

    private function loadExposedAttribute(PropertyMetadata $metadata, array $annotations, ClassMetadata $classMetadata)
    {
        $metadata->readOnly = $metadata->readOnly || $classMetadata->readOnly;
        $accessType = $classMetadata->defaultAccessType;

        $accessor = [null, null];

        foreach ($annotations as $annotation) {
            $this->processor->process($annotation, $metadata);

            if ($annotation instanceof Annotation\AccessType) {
                $accessType = $annotation->type;
            } elseif ($annotation instanceof Annotation\Accessor) {
                $accessor = [$annotation->getter, $annotation->setter];
            }
        }

        $metadata->setAccessor($accessType, $accessor[0], $accessor[1]);
        $classMetadata->addAttributeMetadata($metadata);
    }

    protected function isExcluded(\ReflectionClass $class)
    {
        return null !== $this->reader->getClassAnnotation($class, Annotation\Exclude::class);
    }

    protected function getClassAnnotations(ClassMetadata $classMetadata)
    {
        return $this->reader->getClassAnnotations($classMetadata->getReflectionClass());
    }

    protected function getMethodAnnotations(\ReflectionMethod $method)
    {
        return $this->reader->getMethodAnnotations($method);
    }

    protected function getPropertyAnnotations(\ReflectionProperty $property)
    {
        return $this->reader->getPropertyAnnotations($property);
    }

    protected function isPropertyExcluded(\ReflectionProperty $property, ClassMetadata $classMetadata)
    {
        if ($classMetadata->exclusionPolicy === Annotation\ExclusionPolicy::ALL) {
            return null === $this->reader->getPropertyAnnotation($property, Annotation\Expose::class);
        }

        return null !== $this->reader->getPropertyAnnotation($property, Annotation\Exclude::class);
    }
}
