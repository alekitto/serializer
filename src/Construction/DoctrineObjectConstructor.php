<?php

declare(strict_types=1);

namespace Kcs\Serializer\Construction;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Kcs\Serializer\DeserializationContext;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;
use SplObjectStorage;

use function array_key_exists;
use function count;
use function is_array;

/**
 * Doctrine object constructor for new (or existing) objects during deserialization.
 */
class DoctrineObjectConstructor implements ObjectConstructorInterface
{
    /** @var SplObjectStorage<ManagerRegistry> */
    private SplObjectStorage $managerRegistryCollection;

    /**
     * @param ObjectConstructorInterface $fallbackConstructor Fallback object constructor
     */
    public function __construct(private ObjectConstructorInterface $fallbackConstructor)
    {
        $this->managerRegistryCollection = new SplObjectStorage();
    }

    /**
     * {@inheritdoc}
     */
    public function construct(VisitorInterface $visitor, ClassMetadata $metadata, mixed $data, Type $type, DeserializationContext $context): object
    {
        $object = $this->loadFromObjectManager($metadata, $data);

        return $object ?? $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
    }

    /**
     * Add a manager registry to the collection.
     */
    public function addManagerRegistry(ManagerRegistry $managerRegistry): self
    {
        $this->managerRegistryCollection->attach($managerRegistry);

        return $this;
    }

    /**
     * Get the object manager handling the specified class
     * Returns NULL if cannot be found.
     */
    protected function getObjectManager(ClassMetadata $metadata): ?ObjectManager
    {
        foreach ($this->managerRegistryCollection as $managerRegistry) {
            $objectManager = $managerRegistry->getManagerForClass($metadata->getName());
            if ($objectManager !== null) {
                return $objectManager;
            }
        }

        return null;
    }

    /**
     * Try to load an object from doctrine.
     */
    protected function loadFromObjectManager(ClassMetadata $metadata, mixed $data): ?object
    {
        // Locate possible ObjectManager
        $objectManager = $this->getObjectManager($metadata);
        if ($objectManager === null) {
            return null;
        }

        // Locate possible ClassMetadata
        $classMetadataFactory = $objectManager->getMetadataFactory();

        if ($classMetadataFactory->isTransient($metadata->getName())) {
            // No ClassMetadata found, proceed with normal deserialization
            return null;
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
                continue;
            }

            $identifierList[$name] = $data[$name];
        }

        if (count($identifierList) === 0) {
            return null;
        }

        // Entity update, load it from database
        return $objectManager->find($metadata->getName(), $identifierList);
    }
}
