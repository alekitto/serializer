<?php declare(strict_types=1);

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
     * Map of doctrine 2 field types to Kcs\Serializer types.
     *
     * @var string[]
     */
    protected $fieldMapping = [
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

    public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool
    {
        /* @var $classMetadata ClassMetadata */
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
     * @param ClassMetadata         $classMetadata
     */
    abstract protected function setDiscriminator(DoctrineClassMetadata $doctrineMetadata, ClassMetadata $classMetadata): void;

    /**
     * @param DoctrineClassMetadata $doctrineMetadata
     * @param PropertyMetadata      $propertyMetadata
     *
     * @return bool
     */
    abstract protected function hideProperty(DoctrineClassMetadata $doctrineMetadata, PropertyMetadata $propertyMetadata): bool;

    /**
     * @param DoctrineClassMetadata $doctrineMetadata
     * @param PropertyMetadata      $propertyMetadata
     */
    abstract protected function setPropertyType(DoctrineClassMetadata $doctrineMetadata, PropertyMetadata $propertyMetadata): void;

    /**
     * @param string $className
     *
     * @return null|DoctrineClassMetadata
     */
    protected function tryLoadingDoctrineMetadata($className): ?DoctrineClassMetadata
    {
        if (! $manager = $this->registry->getManagerForClass($className)) {
            return null;
        }

        if ($manager->getMetadataFactory()->isTransient($className)) {
            return null;
        }

        return $manager->getClassMetadata($className);
    }

    protected function normalizeFieldType($type): ?string
    {
        if (! isset($this->fieldMapping[$type])) {
            return null;
        }

        return $this->fieldMapping[$type];
    }
}
