<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader;

use Doctrine\Persistence\Mapping\ClassMetadata as DoctrineClassMetadata;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;

/**
 * This class decorates any other driver. If the inner driver does not provide a
 * a property type, the decorator will guess based on Doctrine 2 metadata.
 */
class DoctrineTypeLoader extends AbstractDoctrineTypeLoader
{
    /**
     * {@inheritdoc}
     */
    protected function hideProperty(DoctrineClassMetadata $doctrineMetadata, PropertyMetadata $propertyMetadata): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function setDiscriminator(DoctrineClassMetadata $doctrineMetadata, ClassMetadata $classMetadata): void
    {
        /** @var \Doctrine\ORM\Mapping\ClassMetadata $doctrineMetadata */
        if (empty($classMetadata->discriminatorMap) && ! $classMetadata->discriminatorDisabled
            && ! empty($doctrineMetadata->discriminatorMap) && $doctrineMetadata->isRootEntity()
        ) {
            $classMetadata->setDiscriminator(
                $doctrineMetadata->discriminatorColumn['name'],
                $doctrineMetadata->discriminatorMap,
                []
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function setPropertyType(DoctrineClassMetadata $doctrineMetadata, PropertyMetadata $propertyMetadata): void
    {
        /** @var \Doctrine\ORM\Mapping\ClassMetadata $doctrineMetadata */
        $propertyName = $propertyMetadata->name;
        if ($doctrineMetadata->hasField($propertyName) && $fieldType = $this->normalizeFieldType($doctrineMetadata->getTypeOfField($propertyName))) {
            $propertyMetadata->setType($fieldType);
        } elseif ($doctrineMetadata->hasAssociation($propertyName)) {
            $targetEntity = $doctrineMetadata->getAssociationTargetClass($propertyName);

            if (null === $targetMetadata = $this->tryLoadingDoctrineMetadata($targetEntity)) {
                return;
            }

            // For inheritance schemes, we cannot add any type as we would only add the super-type of the hierarchy.
            // On serialization, this would lead to only the supertype being serialized, and properties of subtypes
            // being ignored.
            if ($targetMetadata instanceof DoctrineClassMetadata && ! $targetMetadata->isInheritanceTypeNone()) {
                return;
            }

            if (! $doctrineMetadata->isSingleValuedAssociation($propertyName)) {
                $targetEntity = "ArrayCollection<{$targetEntity}>";
            }

            $propertyMetadata->setType($targetEntity);
        }
    }
}
