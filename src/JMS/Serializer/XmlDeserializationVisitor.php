<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
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

namespace JMS\Serializer;

use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\Exception\XmlErrorException;
use JMS\Serializer\Exception\LogicException;
use JMS\Serializer\Exception\InvalidArgumentException;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Metadata\ClassMetadata;

class XmlDeserializationVisitor extends GenericDeserializationVisitor
{
    private $disableExternalEntities = true;
    private $doctypeWhitelist = array();
    private $docNamespaces = array();

    public function enableExternalEntities()
    {
        $this->disableExternalEntities = false;
    }

    public function prepare($data)
    {
        $previous = libxml_use_internal_errors(true);
        $previousEntityLoaderState = libxml_disable_entity_loader($this->disableExternalEntities);

        $dom = new \DOMDocument();
        $dom->loadXML($data);
        foreach ($dom->childNodes as $child) {
            if ($child->nodeType === XML_DOCUMENT_TYPE_NODE) {
                $doctype = $this->getDomDocumentType($data);
                if ( ! in_array($doctype, $this->doctypeWhitelist, true)) {
                    throw new InvalidArgumentException(sprintf(
                        'The document type "%s" is not allowed. If it is safe, you may add it to the whitelist configuration.',
                        $doctype
                    ));
                }
            }
        }

        $doc = simplexml_load_string($data);
        libxml_use_internal_errors($previous);
        libxml_disable_entity_loader($previousEntityLoaderState);

        if (false === $doc) {
            throw new XmlErrorException(libxml_get_last_error());
        }

        $this->docNamespaces = $doc->getDocNamespaces(true);

        return $doc;
    }

    public function visitArray($data, array $type, Context $context)
    {
        $metadataStack = $context->getMetadataStack();
        $currentMetadata = ($metadataStack->count() && $metadataStack->top() instanceof PropertyMetadata) ? $metadataStack->top() : null;

        $entryName = (null !== $currentMetadata && $currentMetadata->xmlEntryName) ? $currentMetadata->xmlEntryName : 'entry';
        $result = array();

        switch (count($type['params'])) {
            case 0:
                throw new RuntimeException(sprintf('The array type must be specified either as "array<T>", or "array<K,V>".'));

            case 1:
                foreach ($data->$entryName as $v) {
                    if ($this->isNullNode($v)) {
                        $result[] = $this->visitNull(null, array(), $context);
                    } else {
                        $result[] = $context->accept($v, $type['params'][0]);
                    }
                }

                break;

            case 2:
                if (null === $currentMetadata) {
                    throw new RuntimeException('Maps are not supported on top-level without metadata.');
                }

                list($keyType, $entryType) = $type['params'];
                foreach ($data->$entryName as $v) {
                    if ( ! isset($v[$currentMetadata->xmlKeyAttribute])) {
                        throw new RuntimeException(sprintf('The key attribute "%s" must be set for each entry of the map.', $currentMetadata->xmlKeyAttribute));
                    }

                    $k = $context->accept($v[$currentMetadata->xmlKeyAttribute], $keyType);

                    if ($this->isNullNode($v)) {
                        $result[$k] = $this->visitNull(null, array(), $context);
                    } else {
                        $result[$k] = $context->accept($v, $entryType);
                    }
                }

                break;

            default:
                throw new LogicException(sprintf('The array type does not support more than 2 parameters, but got %s.', json_encode($type['params'])));
        }

        $this->setData($result);
        return $result;
    }

    public function visitProperty(PropertyMetadata $metadata, $data, Context $context)
    {
        $name = $this->namingStrategy->translateName($metadata);

        if ( ! $metadata->type) {
            throw new RuntimeException(sprintf('You must define a type for %s::$%s.', $metadata->getReflection()->class, $metadata->name));
        }

        if ($metadata->xmlAttribute) {
            if ('' !== $namespace = (string) $metadata->xmlNamespace) {
                $registeredNamespaces = $data->getDocNamespaces();
                if (false === $prefix = array_search($namespace, $registeredNamespaces)) {
                    $prefix = uniqid('ns-');
                    $data->registerXPathNamespace($prefix, $namespace);
                }
                $attributeName = ($prefix === '') ? $name : $prefix.':'.$name;
                $nodes = $data->xpath('./@'.$attributeName);
                if ( ! empty($nodes)) {
                    return (string) reset($nodes);
                }
            } elseif (isset($data[$name])) {
                return $context->accept($data[$name], $metadata->type);
            }

            return null;
        }

        if ($metadata->xmlValue) {
            return $context->accept($data, $metadata->type);
        }

        if ($metadata->xmlCollection) {
            $enclosingElem = $data;
            if ( ! $metadata->xmlCollectionInline && isset($data->$name)) {
                $enclosingElem = $data->$name;
            }

            return $context->accept($enclosingElem, $metadata->type);
        }

        if ('' !== $namespace = (string) $metadata->xmlNamespace) {
            $registeredNamespaces = $data->getDocNamespaces();
            if (false === $prefix = array_search($namespace, $registeredNamespaces)) {
                $prefix = uniqid('ns-');
                $data->registerXPathNamespace($prefix, $namespace);
            }
            $elementName = ($prefix === '') ? $name : $prefix.':'.$name;
            $nodes = $data->xpath('./'.$elementName);
            if (empty($nodes)) {
                return null;
            }
            $node = reset($nodes);
        } else {
            if ( ! isset($data->$name)) {
                return null;
            }
            $node = $data->$name;
        }

        if ($this->isNullNode($node)) {
            return $this->visitNull(null, array(), $context);
        }

        return $context->accept($node, $metadata->type);
    }

    public function visitBoolean($data, array $type, Context $context)
    {
        if ($this->isNullNode($data)) {
            return $this->visitNull(null, array(), $context);
        }

        $data = (string) $data;

        if ('true' === $data || '1' === $data) {
            $data = true;
        } elseif ('false' === $data || '0' === $data) {
            $data = false;
        } else {
            throw new RuntimeException(sprintf('Could not convert data to boolean. Expected "true", "false", "1" or "0", but got %s.', json_encode($data)));
        }

        return parent::visitBoolean($data, $type, $context);
    }

    public function visitCustom(callable $handler, $data, array $type, Context $context)
    {
        if ($this->isNullNode($data)) {
            return $this->visitNull(null, array(), $context);
        }

        return parent::visitCustom($handler, $data, $type, $context);
    }

    public function visitObject(ClassMetadata $metadata, $data, array $type, Context $context, ObjectConstructorInterface $objectConstructor = null)
    {
        if ($this->isNullNode($data)) {
            return $this->visitNull(null, array(), $context);
        }

        return parent::visitObject($metadata, $data, $type, $context, $objectConstructor); // TODO: Change the autogenerated stub
    }

    public function visitString($data, array $type, Context $context)
    {
        if ($this->isNullNode($data)) {
            return $this->visitNull(null, array(), $context);
        }

        return parent::visitString($data, $type, $context); // TODO: Change the autogenerated stub
    }

    public function visitInteger($data, array $type, Context $context)
    {
        if ($this->isNullNode($data)) {
            return $this->visitNull(null, array(), $context);
        }

        return parent::visitInteger($data, $type, $context); // TODO: Change the autogenerated stub
    }

    public function visitDouble($data, array $type, Context $context)
    {
        if ($this->isNullNode($data)) {
            return $this->visitNull(null, array(), $context);
        }

        return parent::visitDouble($data, $type, $context); // TODO: Change the autogenerated stub
    }

    /**
     * @param string[] $doctypeWhitelist
     */
    public function setDoctypeWhitelist(array $doctypeWhitelist)
    {
        $this->doctypeWhitelist = $doctypeWhitelist;
    }

    /**
     * @return string[]
     */
    public function getDoctypeWhitelist()
    {
        return $this->doctypeWhitelist;
    }

    /**
     * Retrieves internalSubset even in bugfixed php versions
     *
     * @param string $data
     * @return string
     */
    private function getDomDocumentType($data)
    {
        $startPos = $endPos = stripos($data, '<!doctype');
        $braces = 0;
        do {
            $char = $data[$endPos++];
            if ($char === '<') {
                ++$braces;
            }
            if ($char === '>') {
                --$braces;
            }
        } while ($braces > 0);

        $internalSubset = substr($data, $startPos, $endPos - $startPos);
        $internalSubset = str_replace(array("\n", "\r"), '', $internalSubset);
        $internalSubset = preg_replace('/\s{2,}/', ' ', $internalSubset);
        $internalSubset = str_replace(array("[ <!", "> ]>"), array('[<!', '>]>'), $internalSubset);

        return $internalSubset;
    }

    private function isNullNode(\SimpleXMLElement $node)
    {
        if (! array_key_exists('xsi', $this->docNamespaces)) {
            return false;
        }

        return ($nilNodes = $node->xpath('./@xsi:nil')) && 'true' === (string) reset($nilNodes);
    }
}
