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

use Kcs\Metadata\ClassMetadataInterface;
use Kcs\Metadata\Loader\FileLoader;
use Kcs\Metadata\MethodMetadata;
use Kcs\Serializer\Annotation\ExclusionPolicy;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Exception\XmlErrorException;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Metadata\VirtualPropertyMetadata;

class XmlLoader extends FileLoader
{
    protected function loadClassMetadataFromFile($file_content, ClassMetadataInterface $metadata)
    {
        /** @var ClassMetadata $metadata */
        $class = $metadata->getReflectionClass();
        $name = $class->name;

        $previous = libxml_use_internal_errors(true);
        $elem = simplexml_load_string($file_content);
        libxml_use_internal_errors($previous);

        if (false === $elem) {
            throw new XmlErrorException(libxml_get_last_error());
        }

        if (! $elems = $elem->xpath("./class[@name = '".$name."']")) {
            return false;
        }
        $elem = reset($elems);

        if (null !== ($exclude = $elem->attributes()->exclude) && 'true' === strtolower($exclude)) {
            return true;
        }

        $metadata->exclusionPolicy = strtoupper($elem->attributes()->{'exclusion-policy'}) ?: ExclusionPolicy::NONE;
        $metadata->defaultAccessType = (string) ($elem->attributes()->{'access-type'} ?: PropertyMetadata::ACCESS_TYPE_PUBLIC_METHOD);

        if (null !== $accessorOrder = $elem->attributes()->{'accessor-order'}) {
            $metadata->setAccessorOrder((string) $accessorOrder, preg_split('/\s*,\s*/', (string) $elem->attributes()->{'custom-accessor-order'}));
        }

        if (null !== $xmlRootName = $elem->attributes()->{'xml-root-name'}) {
            $metadata->xmlRootName = (string) $xmlRootName;
        }

        if (null !== $xmlRootNamespace = $elem->attributes()->{'xml-root-namespace'}) {
            $metadata->xmlRootNamespace = (string) $xmlRootNamespace;
        }

        $metadata->readOnly = 'true' === strtolower($elem->attributes()->{'read-only'});

        $discriminatorFieldName = (string) $elem->attributes()->{'discriminator-field-name'};
        $discriminatorMap = [];
        foreach ($elem->xpath('./discriminator-class') as $entry) {
            if (! isset($entry->attributes()->value)) {
                throw new RuntimeException('Each discriminator-class element must have a "value" attribute.');
            }

            $discriminatorMap[(string) $entry->attributes()->value] = (string) $entry;
        }

        if ('true' === (string) $elem->attributes()->{'discriminator-disabled'}) {
            $metadata->discriminatorDisabled = true;
        } elseif (! empty($discriminatorFieldName) || ! empty($discriminatorMap)) {
            $metadata->setDiscriminator($discriminatorFieldName, $discriminatorMap);
        }

        foreach ($elem->xpath('./xml-namespace') as $xmlNamespace) {
            if (! isset($xmlNamespace->attributes()->uri)) {
                throw new RuntimeException('The prefix attribute must be set for all xml-namespace elements.');
            }

            if (isset($xmlNamespace->attributes()->prefix)) {
                $prefix = (string) $xmlNamespace->attributes()->prefix;
            } else {
                $prefix = null;
            }

            $metadata->registerNamespace((string) $xmlNamespace->attributes()->uri, $prefix);
        }

        foreach ($elem->xpath('./virtual-property') as $method) {
            if (! isset($method->attributes()->method)) {
                throw new RuntimeException('The method attribute must be set for all virtual-property elements.');
            }

            $virtualPropertyMetadata = new VirtualPropertyMetadata($name, (string) $method->attributes()->method);
            $this->loadExposedAttribute($virtualPropertyMetadata, $method, $metadata);
        }

        foreach ($class->getProperties() as $property) {
            if ($name !== $property->getDeclaringClass()->name) {
                continue;
            }

            $pMetadata = new PropertyMetadata($name, $pName = $property->getName());
            $pElems = $elem->xpath("./property[@name = '".$pName."']");

            if (! empty($pElems)) {
                $this->processPropertyMetadata($pMetadata, reset($pElems), $metadata);
            } elseif ($metadata->exclusionPolicy === ExclusionPolicy::NONE) {
                $metadata->addAttributeMetadata($pMetadata);
            }
        }

        foreach ($elem->xpath('./callback-method') as $method) {
            if (! isset($method->attributes()->type)) {
                throw new RuntimeException('The type attribute must be set for all callback-method elements.');
            }
            if (! isset($method->attributes()->name)) {
                throw new RuntimeException('The name attribute must be set for all callback-method elements.');
            }

            switch ((string) $method->attributes()->type) {
                case 'pre-serialize':
                    $metadata->addPreSerializeMethod(new MethodMetadata($name, (string) $method->attributes()->name));
                    break;

                case 'post-serialize':
                    $metadata->addPostSerializeMethod(new MethodMetadata($name, (string) $method->attributes()->name));
                    break;

                case 'post-deserialize':
                    $metadata->addPostDeserializeMethod(new MethodMetadata($name, (string) $method->attributes()->name));
                    break;

                default:
                    throw new RuntimeException(sprintf('The type "%s" is not supported.', $method->attributes()->name));
            }
        }

        return true;
    }

    private function processPropertyMetadata(PropertyMetadata $pMetadata, $pElem, ClassMetadata $metadata)
    {
        $isExclude = false;
        $isExpose = true;

        if (null !== $exclude = $pElem->attributes()->exclude) {
            $isExclude = 'true' === strtolower($exclude);
        }

        if (null !== $expose = $pElem->attributes()->expose) {
            $isExpose = 'true' === strtolower($expose);
        }

        if (($metadata->exclusionPolicy === ExclusionPolicy::NONE && ! $isExclude) ||
            ($metadata->exclusionPolicy === ExclusionPolicy::ALL && $isExpose)) {
            $this->loadExposedAttribute($pMetadata, $pElem, $metadata);
        }
    }

    private function loadExposedAttribute(PropertyMetadata $pMetadata, $pElem, ClassMetadata $metadata)
    {
        if (null !== $version = $pElem->attributes()->{'since-version'}) {
            $pMetadata->sinceVersion = (string) $version;
        }

        if (null !== $version = $pElem->attributes()->{'until-version'}) {
            $pMetadata->untilVersion = (string) $version;
        }

        if (null !== $serializedName = $pElem->attributes()->{'serialized-name'}) {
            $pMetadata->serializedName = (string) $serializedName;
        }

        if (null !== $type = $pElem->attributes()->type) {
            $pMetadata->setType((string) $type);
        } elseif (isset($pElem->type)) {
            $pMetadata->setType((string) $pElem->type);
        }

        if (null !== $groups = $pElem->attributes()->groups) {
            $pMetadata->groups = preg_split('/\s*,\s*/', (string) $groups);
        }

        if (isset($pElem->{'xml-list'})) {
            $pMetadata->xmlCollection = true;

            $colConfig = $pElem->{'xml-list'};
            if (isset($colConfig->attributes()->inline)) {
                $pMetadata->xmlCollectionInline = 'true' === (string) $colConfig->attributes()->inline;
            }

            if (isset($colConfig->attributes()->{'entry-name'})) {
                $pMetadata->xmlEntryName = (string) $colConfig->attributes()->{'entry-name'};
            }

            if (isset($colConfig->attributes()->namespace)) {
                $pMetadata->xmlEntryNamespace = (string) $colConfig->attributes()->namespace;
            }
        }

        if (isset($pElem->{'xml-map'})) {
            $pMetadata->xmlCollection = true;

            $colConfig = $pElem->{'xml-map'};
            if (isset($colConfig->attributes()->inline)) {
                $pMetadata->xmlCollectionInline = 'true' === (string) $colConfig->attributes()->inline;
            }

            if (isset($colConfig->attributes()->{'entry-name'})) {
                $pMetadata->xmlEntryName = (string) $colConfig->attributes()->{'entry-name'};
            }

            if (isset($colConfig->attributes()->{'key-attribute-name'})) {
                $pMetadata->xmlKeyAttribute = (string) $colConfig->attributes()->{'key-attribute-name'};
            }

            if (isset($colConfig->attributes()->namespace)) {
                $pMetadata->xmlEntryNamespace = (string) $colConfig->attributes()->namespace;
            }
        }

        if (isset($pElem->{'xml-element'})) {
            $colConfig = $pElem->{'xml-element'};
            if (isset($colConfig->attributes()->cdata)) {
                $pMetadata->xmlElementCData = 'true' === (string) $colConfig->attributes()->cdata;
            }

            if (isset($colConfig->attributes()->namespace)) {
                $pMetadata->xmlNamespace = (string) $colConfig->attributes()->namespace;
            }
        }

        if (isset($pElem->attributes()->{'xml-attribute'})) {
            $pMetadata->xmlAttribute = 'true' === (string) $pElem->attributes()->{'xml-attribute'};
        }

        if (isset($pElem->attributes()->{'xml-attribute-map'})) {
            $pMetadata->xmlAttribute = 'true' === (string) $pElem->attributes()->{'xml-attribute-map'};
        }

        if (isset($pElem->attributes()->{'xml-value'})) {
            $pMetadata->xmlValue = 'true' === (string) $pElem->attributes()->{'xml-value'};
        }

        if (isset($pElem->attributes()->{'xml-key-value-pairs'})) {
            $pMetadata->xmlKeyValuePairs = 'true' === (string) $pElem->attributes()->{'xml-key-value-pairs'};
        }

        if (isset($pElem->attributes()->{'max-depth'})) {
            $pMetadata->maxDepth = (int) $pElem->attributes()->{'max-depth'};
        }

        //we need read-only before setter and getter set, because that method depends on flag being set
        if (null !== $readOnly = $pElem->attributes()->{'read-only'}) {
            $pMetadata->readOnly = 'true' === strtolower($readOnly);
        } else {
            $pMetadata->readOnly = $pMetadata->readOnly || $metadata->readOnly;
        }

        $getter = $pElem->attributes()->{'accessor-getter'};
        $setter = $pElem->attributes()->{'accessor-setter'};
        $pMetadata->setAccessor(
            (string) ($pElem->attributes()->{'access-type'} ?: $metadata->defaultAccessType),
            $getter ? (string) $getter : null,
            $setter ? (string) $setter : null
        );

        if (null !== $inline = $pElem->attributes()->inline) {
            $pMetadata->inline = 'true' === strtolower($inline);
        }

        $metadata->addAttributeMetadata($pMetadata);
    }

    protected function getExtension()
    {
        return 'xml';
    }
}
