<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 * Copyright 2016 Alessandro Chitolina <alekitto@gmail.com>
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

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as DoctrineClassMetadata;
use Kcs\Metadata\ClassMetadataInterface;
use Kcs\Metadata\Loader\LoaderInterface;
use Kcs\Metadata\NullMetadata;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;

/**
 * This class decorates any other driver. If the inner driver does not provide a
 * a property type, the decorator will guess based on Doctrine 2 metadata.
 */
abstract class AbstractDoctrineTypeLoader implements LoaderInterface
{
    /**
     * Map of doctrine 2 field types to Kcs\Serializer types
     * @const array
     */
    const FIELD_MAPPING = [
        'string' => 'string',
        'text' => 'string',
        'blob' => 'string',

        'integer' => 'integer',
        'smallint' => 'integer',
        'bigint' => 'integer',

        'datetime' => 'DateTime',
        'datetimetz' => 'DateTime',
        'time' => 'DateTime',
        'date' => 'DateTime',

        'float' => 'float',
        'decimal' => 'float',

        'boolean' => 'boolean',

        'array' => 'array',
        'json_array' => 'array',
        'simple_array' => 'array<string>',
    ];

    /**
     * @var LoaderInterface
     */
    protected $delegate;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(LoaderInterface $delegate, ManagerRegistry $registry)
    {
        $this->delegate = $delegate;
        $this->registry = $registry;
    }

    public function loadClassMetadata(ClassMetadataInterface $classMetadata)
    {
        /** @var $classMetadata ClassMetadata */
        $this->delegate->loadClassMetadata($classMetadata);

        // Abort if the given class is not a mapped entity
        if (! $doctrineMetadata = $this->tryLoadingDoctrineMetadata($classMetadata->getName())) {
            return true;
        }

        $this->setDiscriminator($doctrineMetadata, $classMetadata);

        // We base our scan on the internal driver's property list so that we
        // respect any internal white/blacklisting like in the AnnotationDriver
        foreach ($classMetadata->getAttributesMetadata() as $key => $propertyMetadata) {
            if (! $propertyMetadata instanceof PropertyMetadata) {
                continue;
            }

            // If the inner driver provides a type, don't guess anymore.
            if (null !== $propertyMetadata->type) {
                continue;
            }

            if ($this->hideProperty($doctrineMetadata, $propertyMetadata)) {
                $classMetadata->addAttributeMetadata(new NullMetadata($key));
            }

            $this->setPropertyType($doctrineMetadata, $propertyMetadata);
        }

        return true;
    }

    /**
     * @param DoctrineClassMetadata $doctrineMetadata
     * @param ClassMetadata $classMetadata
     */
    abstract protected function setDiscriminator(DoctrineClassMetadata $doctrineMetadata, ClassMetadata $classMetadata);

    /**
     * @param DoctrineClassMetadata $doctrineMetadata
     * @param PropertyMetadata $propertyMetadata
     *
     * @return bool
     */
    abstract protected function hideProperty(DoctrineClassMetadata $doctrineMetadata, PropertyMetadata $propertyMetadata);

    /**
     * @param DoctrineClassMetadata $doctrineMetadata
     * @param PropertyMetadata $propertyMetadata
     */
    abstract protected function setPropertyType(DoctrineClassMetadata $doctrineMetadata, PropertyMetadata $propertyMetadata);

    /**
     * @param string $className
     *
     * @return null|DoctrineClassMetadata
     */
    protected function tryLoadingDoctrineMetadata($className)
    {
        if (! $manager = $this->registry->getManagerForClass($className)) {
            return null;
        }

        if ($manager->getMetadataFactory()->isTransient($className)) {
            return null;
        }

        return $manager->getClassMetadata($className);
    }

    protected function normalizeFieldType($type)
    {
        if (! array_key_exists($type, static::FIELD_MAPPING)) {
            return null;
        }

        return static::FIELD_MAPPING[$type];
    }
}
