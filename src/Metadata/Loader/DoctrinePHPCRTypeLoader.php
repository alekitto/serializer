<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader;

use Doctrine\Persistence\Mapping\ClassMetadata as DoctrineClassMetadata;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Throwable;

use function assert;
use function sprintf;

/**
 * This class decorates any other driver. If the inner driver does not provide a
 * a property type, the decorator will guess based on Doctrine 2 metadata.
 */
class DoctrinePHPCRTypeLoader extends AbstractDoctrineTypeLoader
{
    protected function setDiscriminator(DoctrineClassMetadata $doctrineMetadata, ClassMetadata $classMetadata): void
    {
        // Do nothing
    }

    protected function hideProperty(DoctrineClassMetadata $doctrineMetadata, PropertyMetadata $propertyMetadata): bool
    {
        assert($doctrineMetadata instanceof \Doctrine\ODM\PHPCR\Mapping\ClassMetadata);

        return $propertyMetadata->name === 'lazyPropertiesDefaults'
            || $doctrineMetadata->parentMapping === $propertyMetadata->name
            || $doctrineMetadata->node === $propertyMetadata->name;
    }

    protected function setPropertyType(DoctrineClassMetadata $doctrineMetadata, PropertyMetadata $propertyMetadata): void
    {
        assert($doctrineMetadata instanceof \Doctrine\ODM\PHPCR\Mapping\ClassMetadata);

        $propertyName = $propertyMetadata->name;
        $typeName = $doctrineMetadata->hasField($propertyName) ? $doctrineMetadata->getTypeOfField($propertyName) : null;
        $fieldType = $typeName !== null ? $this->normalizeFieldType((string) $typeName) : null;
        if ($fieldType !== null) {
            $field = $doctrineMetadata->getFieldMapping($propertyName);
            if (! empty($field['multivalue'])) {
                $fieldType = 'array';
            }

            $propertyMetadata->setType($fieldType);
        } elseif ($doctrineMetadata->hasAssociation($propertyName)) {
            try {
                $targetEntity = $doctrineMetadata->getAssociationTargetClass($propertyName);
            } catch (Throwable) { // @phpstan-ignore-line
                return;
            }

            if ($targetEntity === null || $this->tryLoadingDoctrineMetadata($targetEntity) === null) {
                return;
            }

            if (! $doctrineMetadata->isSingleValuedAssociation($propertyName)) {
                $targetEntity = sprintf('ArrayCollection<%s>', $targetEntity);
            }

            $propertyMetadata->setType($targetEntity);
        }
    }
}
