<?php declare(strict_types=1);

namespace Kcs\Serializer\Construction;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Kcs\Serializer\DeserializationContext;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;
use SplObjectStorage;

/**
 * Doctrine object constructor for new (or existing) objects during deserialization.
 */
class DoctrineObjectConstructor implements ObjectConstructorInterface
{
    /**
     * @var SplObjectStorage<ManagerRegistry>
     */
    private SplObjectStorage $managerRegistryCollection;
    private ObjectConstructorInterface $fallbackConstructor;

    /**
     * Constructor.
     *
     * @param ObjectConstructorInterface $fallbackConstructor Fallback object constructor
     */
    public function __construct(ObjectConstructorInterface $fallbackConstructor)
    {
        $this->fallbackConstructor = $fallbackConstructor;
        $this->managerRegistryCollection = new SplObjectStorage();
    }

    /**
     * {@inheritdoc}
     */
    public function construct(VisitorInterface $visitor, ClassMetadata $metadata, $data, Type $type, DeserializationContext $context): object
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
     *
     * @param ClassMetadata $metadata
     *
     * @return ObjectManager|null
     */
    protected function getObjectManager(ClassMetadata $metadata): ?ObjectManager
    {
        foreach ($this->managerRegistryCollection as $managerRegistry) {
            if ($objectManager = $managerRegistry->getManagerForClass($metadata->getName())) {
                return $objectManager;
            }
        }

        return null;
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
            return null;
        }

        // Locate possible ClassMetadata
        $classMetadataFactory = $objectManager->getMetadataFactory();

        if ($classMetadataFactory->isTransient($metadata->getName())) {
            // No ClassMetadata found, proceed with normal deserialization
            return null;
        }

        if (! \is_array($data)) {
            // Single identifier, load
            return $objectManager->find($metadata->getName(), $data);
        }

        // Fallback to default constructor if missing identifier(s)
        $classMetadata = $objectManager->getClassMetadata($metadata->getName());
        $identifierList = [];

        foreach ($classMetadata->getIdentifierFieldNames() as $name) {
            if (! \array_key_exists($name, $data)) {
                continue;
            }

            $identifierList[$name] = $data[$name];
        }

        if (0 === \count($identifierList)) {
            return null;
        }

        // Entity update, load it from database
        return $objectManager->find($metadata->getName(), $identifierList);
    }
}
