<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader;

use Doctrine\Persistence\Mapping\ClassMetadata as DoctrineClassMetadata;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;

use function assert;
use function sprintf;

/**
 * This class decorates any other driver. If the inner driver does not provide a
 * a property type, the decorator will guess based on Doctrine 2 metadata.
 */
class DoctrineTypeLoader extends AbstractDoctrineTypeLoader
{
    protected function hideProperty(DoctrineClassMetadata $doctrineMetadata, PropertyMetadata $propertyMetadata): bool
    {
        return false;
    }

    protected function setDiscriminator(DoctrineClassMetadata $doctrineMetadata, ClassMetadata $classMetadata): void
    {
        assert($doctrineMetadata instanceof \Doctrine\ORM\Mapping\ClassMetadata);

        if (
            ! empty($classMetadata->discriminatorMap) || $classMetadata->discriminatorDisabled
            || empty($doctrineMetadata->discriminatorMap) || ! $doctrineMetadata->isRootEntity()
        ) {
            return;
        }

        assert(isset($doctrineMetadata->discriminatorColumn));
        $classMetadata->setDiscriminator(
            $doctrineMetadata->discriminatorColumn['name'],
            $doctrineMetadata->discriminatorMap, // @phpstan-ignore-line
            [],
        );
    }

    protected function setPropertyType(DoctrineClassMetadata $doctrineMetadata, PropertyMetadata $propertyMetadata): void
    {
        assert($doctrineMetadata instanceof \Doctrine\ORM\Mapping\ClassMetadata);

        $propertyName = $propertyMetadata->name;
        $typeOfField = $doctrineMetadata->hasField($propertyName) ? $doctrineMetadata->getTypeOfField($propertyName) : null;
        $fieldType = $typeOfField !== null ? $this->normalizeFieldType($typeOfField) : null;
        if ($fieldType) {
            $propertyMetadata->setType($fieldType);
        } elseif ($doctrineMetadata->hasAssociation($propertyName)) {
            $targetEntity = $doctrineMetadata->getAssociationTargetClass($propertyName);
            $targetMetadata = $this->tryLoadingDoctrineMetadata($targetEntity);
            if ($targetMetadata === null) {
                return;
            }

            // For inheritance schemes, we cannot add any type as we would only add the super-type of the hierarchy.
            // On serialization, this would lead to only the supertype being serialized, and properties of subtypes
            // being ignored.
            if (! $targetMetadata->isInheritanceTypeNone()) { // @phpstan-ignore-line
                return;
            }

            if (! $doctrineMetadata->isSingleValuedAssociation($propertyName)) {
                $targetEntity = sprintf('ArrayCollection<%s>', $targetEntity);
            }

            $propertyMetadata->setType($targetEntity);
        }
    }
}
