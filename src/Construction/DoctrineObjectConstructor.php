<?php declare(strict_types=1);
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

namespace Kcs\Serializer\Construction;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Kcs\Serializer\DeserializationContext;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;

/**
 * Doctrine object constructor for new (or existing) objects during deserialization.
 */
class DoctrineObjectConstructor implements ObjectConstructorInterface
{
    /**
     * @var \SplObjectStorage|ManagerRegistry[]
     */
    private $managerRegistryCollection;

    /**
     * @var ObjectConstructorInterface
     */
    private $fallbackConstructor;

    /**
     * Constructor.
     *
     * @param ObjectConstructorInterface $fallbackConstructor Fallback object constructor
     */
    public function __construct(ObjectConstructorInterface $fallbackConstructor)
    {
        $this->fallbackConstructor = $fallbackConstructor;
        $this->managerRegistryCollection = new \SplObjectStorage();
    }

    /**
     * {@inheritdoc}
     */
    public function construct(VisitorInterface $visitor, ClassMetadata $metadata, $data, Type $type, DeserializationContext $context)
    {
        $object = $this->loadFromObjectManager($metadata, $data);

        return $object ?: $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
    }

    /**
     * Add a manager registry to the collection.
     *
     * @param ManagerRegistry $managerRegistry
     *
     * @return $this
     */
    public function addManagerRegistry(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistryCollection->attach($managerRegistry);

        return $this;
    }

    /**
     * Get the object manager handling the specified class
     * Returns NULL if cannot be found.
     *
     * @param ClassMetadata $metadata
     *
     * @return null|ObjectManager
     */
    protected function getObjectManager(ClassMetadata $metadata)
    {
        foreach ($this->managerRegistryCollection as $managerRegistry) {
            if ($objectManager = $managerRegistry->getManagerForClass($metadata->getName())) {
                return $objectManager;
            }
        }
    }

    /**
     * Try to load an object from doctrine.
     *
     * @param ClassMetadata $metadata
     * @param $data
     *
     * @return object|null
     */
    protected function loadFromObjectManager(ClassMetadata $metadata, $data)
    {
        // Locate possible ObjectManager
        if (null === $objectManager = $this->getObjectManager($metadata)) {
            return;
        }

        // Locate possible ClassMetadata
        $classMetadataFactory = $objectManager->getMetadataFactory();

        if ($classMetadataFactory->isTransient($metadata->getName())) {
            // No ClassMetadata found, proceed with normal deserialization
            return;
        }

        if (! is_array($data)) {
            // Single identifier, load
            return $objectManager->find($metadata->getName(), $data);
        }

        // Fallback to default constructor if missing identifier(s)
        $classMetadata = $objectManager->getClassMetadata($metadata->getName());
        $identifierList = [];

        foreach ($classMetadata->getIdentifierFieldNames() as $name) {
            if (! array_key_exists($name, $data)) {
                return;
            }

            $identifierList[$name] = $data[$name];
        }

        // Entity update, load it from database
        return $objectManager->find($metadata->getName(), $identifierList);
    }
}
