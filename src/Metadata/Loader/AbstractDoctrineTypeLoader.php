<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata as DoctrineClassMetadata;
use Kcs\Metadata\ClassMetadataInterface;
use Kcs\Metadata\Loader\LoaderInterface;
use Kcs\Metadata\NullMetadata;
use Kcs\Serializer\Metadata\AdditionalPropertyMetadata;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Metadata\StaticPropertyMetadata;
use Kcs\Serializer\Metadata\VirtualPropertyMetadata;

use function assert;

/**
 * This class decorates any other driver. If the inner driver does not provide a
 * a property type, the decorator will guess based on Doctrine 2 metadata.
 */
abstract class AbstractDoctrineTypeLoader implements LoaderInterface
{
    /**
     * Map of doctrine 2 field types to Kcs\Serializer types.
     */
    protected const FIELD_MAPPING = [
        'ascii_string' => 'string',
        'string' => 'string',
        'text' => 'string',
        'blob' => 'string',
        'binary' => 'string',

        'integer' => 'integer',
        'smallint' => 'integer',
        'bigint' => 'integer',

        'datetime' => 'DateTime',
        'datetime_immutable' => 'DateTimeImmutable',
        'datetimetz' => 'DateTime',
        'datetimetz_immutable' => 'DateTimeImmutable',
        'time' => 'DateTime',
        'time_immutable' => 'DateTimeImmutable',
        'date' => 'DateTime',
        'date_immutable' => 'DateTimeImmutable',

        'float' => 'float',
        'decimal' => 'float',

        'boolean' => 'boolean',

        'array' => 'array',
        'json' => 'array',
        'json_array' => 'array',
        'simple_array' => 'array<string>',
    ];

    public function __construct(protected LoaderInterface $delegate, protected ManagerRegistry $registry)
    {
    }

    public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool
    {
        assert($classMetadata instanceof ClassMetadata);
        $this->delegate->loadClassMetadata($classMetadata);

        // Abort if the given class is not a mapped entity
        $doctrineMetadata = $this->tryLoadingDoctrineMetadata($classMetadata->getName());
        if ($doctrineMetadata === null) {
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
            if ($propertyMetadata->type !== null) {
                continue;
            }

            // Virtual property or derived property: type should not be guessed.
            if (
                $propertyMetadata instanceof VirtualPropertyMetadata ||
                $propertyMetadata instanceof StaticPropertyMetadata ||
                $propertyMetadata instanceof AdditionalPropertyMetadata
            ) {
                continue;
            }

            if ($this->hideProperty($doctrineMetadata, $propertyMetadata)) {
                $classMetadata->addAttributeMetadata(new NullMetadata($key));
            }

            $this->setPropertyType($doctrineMetadata, $propertyMetadata);
        }

        return true;
    }

    abstract protected function setDiscriminator(DoctrineClassMetadata $doctrineMetadata, ClassMetadata $classMetadata): void;

    abstract protected function hideProperty(DoctrineClassMetadata $doctrineMetadata, PropertyMetadata $propertyMetadata): bool;

    abstract protected function setPropertyType(DoctrineClassMetadata $doctrineMetadata, PropertyMetadata $propertyMetadata): void;

    /** @phpstan-param class-string<object> $className */
    protected function tryLoadingDoctrineMetadata(string $className): DoctrineClassMetadata|null
    {
        $manager = $this->registry->getManagerForClass($className);
        if ($manager === null) {
            return null;
        }

        if ($manager->getMetadataFactory()->isTransient($className)) {
            return null;
        }

        return $manager->getClassMetadata($className);
    }

    protected function normalizeFieldType(string $type): string|null
    {
        return self::FIELD_MAPPING[$type] ?? null;
    }
}
