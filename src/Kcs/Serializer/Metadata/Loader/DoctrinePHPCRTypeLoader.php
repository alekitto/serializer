<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 * Modifications copyright (c) 2016 Alessandro Chitolina <alekitto@gmail.com>
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

use Doctrine\Common\Persistence\Mapping\ClassMetadata as DoctrineClassMetadata;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;

/**
 * This class decorates any other driver. If the inner driver does not provide a
 * a property type, the decorator will guess based on Doctrine 2 metadata.
 */
class DoctrinePHPCRTypeLoader extends AbstractDoctrineTypeLoader
{
    protected function setDiscriminator(DoctrineClassMetadata $doctrineMetadata, ClassMetadata $classMetadata)
    {
        // Do nothing
    }

    protected function hideProperty(DoctrineClassMetadata $doctrineMetadata, PropertyMetadata $propertyMetadata)
    {
        /** @var \Doctrine\ODM\PHPCR\Mapping\ClassMetadata $doctrineMetadata */

        return 'lazyPropertiesDefaults' === $propertyMetadata->name
            || $doctrineMetadata->parentMapping === $propertyMetadata->name
            || $doctrineMetadata->node === $propertyMetadata->name;
    }

    protected function setPropertyType(DoctrineClassMetadata $doctrineMetadata, PropertyMetadata $propertyMetadata)
    {
        /** @var \Doctrine\ODM\PHPCR\Mapping\ClassMetadata $doctrineMetadata */

        $propertyName = $propertyMetadata->name;
        if ($doctrineMetadata->hasField($propertyName) && $fieldType = $this->normalizeFieldType($doctrineMetadata->getTypeOfField($propertyName))) {
            $field = $doctrineMetadata->getFieldMapping($propertyName);
            if ( ! empty($field['multivalue'])) {
                $fieldType = 'array';
            }

            $propertyMetadata->setType($fieldType);
        } elseif ($doctrineMetadata->hasAssociation($propertyName)) {
            try {
                $targetEntity = $doctrineMetadata->getAssociationTargetClass($propertyName);
            } catch (\Exception $e) {
                return;
            }

            if (null === $this->tryLoadingDoctrineMetadata($targetEntity)) {
                return;
            }

            if ( ! $doctrineMetadata->isSingleValuedAssociation($propertyName)) {
                $targetEntity = "ArrayCollection<{$targetEntity}>";
            }

            $propertyMetadata->setType($targetEntity);
        }
    }
}
