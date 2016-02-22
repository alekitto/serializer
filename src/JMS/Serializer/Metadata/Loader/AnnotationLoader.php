<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
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

namespace JMS\Serializer\Metadata\Loader;

use Doctrine\Common\Annotations\Reader;
use JMS\Serializer\Annotation;
use JMS\Serializer\Exception\InvalidArgumentException;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Metadata\VirtualPropertyMetadata;
use Kcs\Metadata\ClassMetadataInterface;
use Kcs\Metadata\Loader\LoaderInterface;
use Kcs\Metadata\MethodMetadata;

class AnnotationLoader implements LoaderInterface
{
    private $reader;

    public function __construct(Reader $reader)
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

        $excludeAnnotation = $this->reader->getClassAnnotation($class, Annotation\Exclude::class);
        if (null !== $excludeAnnotation) {
            return true;
        }

        $this->processClassAnnotations($classMetadata);

        foreach ($class->getMethods() as $method) {
            if ($method->class !== $class->name) {
                continue;
            }

            $this->processMethodAnnotations($method, $classMetadata);
        }

        foreach ($class->getProperties() as $property) {
            if ($property->class !== $class->name) {
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
        $annotations = $this->reader->getClassAnnotations($classMetadata->getReflectionClass());
        foreach ($annotations as $annotation) {
            switch (true) {
                case $annotation instanceof Annotation\ExclusionPolicy: {
                    $classMetadata->exclusionPolicy = $annotation->policy;
                }
                    break;

                case $annotation instanceof Annotation\XmlRoot: {
                    $classMetadata->xmlRootName = $annotation->name;
                    $classMetadata->xmlRootNamespace = $annotation->namespace;
                }
                    break;

                case $annotation instanceof Annotation\XmlNamespace: {
                    $classMetadata->registerNamespace($annotation->uri, $annotation->prefix);
                }
                    break;

                case $annotation instanceof Annotation\AccessType: {
                    $classMetadata->defaultAccessType = $annotation->type;
                }
                    break;

                case $annotation instanceof Annotation\ReadOnly: {
                    $classMetadata->readOnly = true;
                }
                    break;

                case $annotation instanceof Annotation\AccessorOrder: {
                    $classMetadata->setAccessorOrder($annotation->order, $annotation->custom);
                }
                    break;

                case $annotation instanceof Annotation\Discriminator: {
                    if ($annotation->disabled) {
                        $classMetadata->discriminatorDisabled = true;
                    } else {
                        $classMetadata->setDiscriminator($annotation->field, $annotation->map);
                    }
                }
                    break;
            }
        }
    }

    private function processMethodAnnotations(\ReflectionMethod $method, ClassMetadata $classMetadata)
    {
        $class = $method->class;

        $methodAnnotations = $this->reader->getMethodAnnotations($method);
        foreach ($methodAnnotations as $annotation) {
            switch (true) {
                case $annotation instanceof Annotation\PreSerialize: {
                    $classMetadata->addPreSerializeMethod(new MethodMetadata($class, $method->name));
                } break;

                case $annotation instanceof Annotation\PostDeserialize: {
                    $classMetadata->addPostDeserializeMethod(new MethodMetadata($class, $method->name));
                } break;

                case $annotation instanceof Annotation\PostSerialize: {
                    $classMetadata->addPostSerializeMethod(new MethodMetadata($class, $method->name));
                } break;

                case $annotation instanceof Annotation\HandlerCallback: {
                    $classMetadata->addHandlerCallback(
                        GraphNavigator::parseDirection($annotation->direction),
                        $annotation->format,
                        $method->name
                    );
                } break;
            }
        }

        if (null !== $this->reader->getMethodAnnotation($method, Annotation\VirtualProperty::class)) {
            $virtualPropertyMetadata = new VirtualPropertyMetadata($class, $method->name);
            $this->loadExposedAttribute($virtualPropertyMetadata, $methodAnnotations, $classMetadata);
        }
    }

    private function processPropertyAnnotations(\ReflectionProperty $property, ClassMetadata $classMetadata)
    {
        $class = $property->class;

        $metadata = new PropertyMetadata($class, $property->name);
        $annotations = $this->reader->getPropertyAnnotations($property);

        if (
            ($classMetadata->exclusionPolicy === Annotation\ExclusionPolicy::NONE &&
            null === $this->reader->getPropertyAnnotation($property, Annotation\Exclude::class)) ||
            ($classMetadata->exclusionPolicy === Annotation\ExclusionPolicy::ALL &&
            null !== $this->reader->getPropertyAnnotation($property, Annotation\Expose::class))
        ) {
            $this->loadExposedAttribute($metadata, $annotations, $classMetadata);
        }
    }

    private function loadExposedAttribute(PropertyMetadata $metadata, array $annotations, ClassMetadata $classMetadata)
    {
        $metadata->readOnly = $metadata->readOnly || $classMetadata->readOnly;
        $accessType = $classMetadata->defaultAccessType;

        $accessor = [null, null];

        foreach ($annotations as $annotation) {
            switch (true) {
                case ($annotation instanceof Annotation\Since): {
                    $metadata->sinceVersion = $annotation->version;
                } break;

                case ($annotation instanceof Annotation\Until): {
                    $metadata->untilVersion = $annotation->version;
                } break;

                case ($annotation instanceof Annotation\SerializedName): {
                    $metadata->serializedName = $annotation->name;
                } break;

                case ($annotation instanceof Annotation\Type): {
                    $metadata->setType($annotation->name);
                } break;

                case ($annotation instanceof Annotation\XmlElement): {
                    $metadata->xmlAttribute = false;
                    $metadata->xmlElementCData = $annotation->cdata;
                    $metadata->xmlNamespace = $annotation->namespace;
                } break;

                case ($annotation instanceof Annotation\XmlList): {
                    $metadata->xmlCollection = true;
                    $metadata->xmlCollectionInline = $annotation->inline;
                    $metadata->xmlEntryName = $annotation->entry;
                } break;

                case ($annotation instanceof Annotation\XmlMap): {
                    $metadata->xmlCollection = true;
                    $metadata->xmlCollectionInline = $annotation->inline;
                    $metadata->xmlEntryName = $annotation->entry;
                    $metadata->xmlKeyAttribute = $annotation->keyAttribute;
                } break;

                case ($annotation instanceof Annotation\XmlKeyValuePairs): {
                    $metadata->xmlKeyValuePairs = true;
                } break;

                case ($annotation instanceof Annotation\XmlAttribute): {
                    $metadata->xmlAttribute = true;
                    $metadata->xmlNamespace = $annotation->namespace;
                } break;

                case ($annotation instanceof Annotation\XmlValue): {
                    $metadata->xmlValue = true;
                    $metadata->xmlElementCData = $annotation->cdata;
                } break;

                case ($annotation instanceof Annotation\XmlElement): {
                    $metadata->xmlElementCData = $annotation->cdata;
                } break;

                case ($annotation instanceof Annotation\AccessType): {
                    $accessType = $annotation->type;
                } break;

                case ($annotation instanceof Annotation\ReadOnly): {
                    $metadata->readOnly = $annotation->readOnly;
                } break;

                case ($annotation instanceof Annotation\Accessor): {
                    $accessor = array($annotation->getter, $annotation->setter);
                } break;

                case ($annotation instanceof Annotation\Groups): {
                    $metadata->groups = $annotation->groups;
                    foreach ((array)$metadata->groups as $groupName) {
                        if (false !== strpos($groupName, ',')) {
                            throw new InvalidArgumentException(sprintf(
                                'Invalid group name "%s" on "%s", did you mean to create multiple groups?',
                                implode(', ', $metadata->groups),
                                $metadata->class . '->' . $metadata->name
                            ));
                        }
                    }
                } break;

                case ($annotation instanceof Annotation\Inline): {
                    $metadata->inline = true;
                } break;

                case ($annotation instanceof Annotation\XmlAttributeMap): {
                    $metadata->xmlAttributeMap = true;
                } break;

                case ($annotation instanceof Annotation\MaxDepth): {
                    $metadata->maxDepth = $annotation->depth;
                } break;

            }
        }

        $metadata->setAccessor($accessType, $accessor[0], $accessor[1]);
        $classMetadata->addAttributeMetadata($metadata);
    }
}
