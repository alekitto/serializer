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

namespace Kcs\Serializer;

use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Type\Type;

/**
 * XmlSerializationVisitor.
 */
class XmlSerializationVisitor extends AbstractVisitor
{
    /**
     * @var \DOMDocument
     */
    public $document;

    private $defaultVersion = '1.0';
    private $defaultEncoding = 'UTF-8';
    private $attachNullNamespace = false;

    /**
     * @var \SplStack
     */
    private $nodeStack;

    /**
     * @var \DOMNode[]|\DOMNode
     */
    private $currentNodes;

    /**
     * @var array
     */
    private $validatedMetadata;

    public function visitNull($data, Type $type, Context $context)
    {
        $this->attachNullNamespace = true;

        $node = $this->document->createAttribute('xsi:nil');
        $node->value = 'true';

        return $this->currentNodes = $node;
    }

    public function visitSimpleString($data)
    {
        return $this->currentNodes = $this->createTextNode((string) $data);
    }

    public function visitString($data, Type $type, Context $context)
    {
        /** @var PropertyMetadata $metadata */
        $metadata = $this->getCurrentPropertyMetadata($context);

        return $this->currentNodes = $this->createTextNode($data, $metadata ? $metadata->xmlElementCData : true);
    }

    public function visitInteger($data, Type $type, Context $context)
    {
        return $this->currentNodes = $this->createTextNode($data);
    }

    public function visitBoolean($data, Type $type, Context $context)
    {
        return $this->currentNodes = $this->createTextNode($data ? 'true' : 'false');
    }

    public function visitDouble($data, Type $type, Context $context)
    {
        return $this->currentNodes = $this->createTextNode($data);
    }

    public function visitObject(ClassMetadata $metadata, $data, Type $type, Context $context, ObjectConstructorInterface $objectConstructor = null)
    {
        if (isset($metadata->handlerCallbacks[$context->getDirection()][$context->getFormat()])) {
            $callback = $metadata->handlerCallbacks[$context->getDirection()][$context->getFormat()];
            $this->currentNodes = $data->$callback($this, null, $context);

            if ($this->nodeStack->count() === 1 && $this->document->documentElement === null) {
                $this->document->appendChild($this->currentNodes);
                $this->currentNodes = [];
            }

            return $this->currentNodes;
        }

        $properties = $context->getNonSkippedProperties($metadata);
        $this->validateObjectProperties($metadata, $properties);

        if ($this->nodeStack->count() === 1 && $this->document->documentElement === null) {
            $rootName = $metadata->xmlRootName ?: 'result';
            if (($rootNamespace = $metadata->xmlRootNamespace)) {
                $rootNode = $this->document->createElementNS($rootNamespace, $rootName);
            } else {
                $rootNode = $this->document->createElement($rootName);
            }

            $this->document->appendChild($rootNode);

            $this->nodeStack->pop();
            $this->nodeStack->push($rootNode);
        }

        $nodes = [];

        /** @var \DOMElement $prevNode */
        $prevNode = $this->nodeStack->top();
        foreach ($metadata->xmlNamespaces as $prefix => $uri) {
            $attribute = 'xmlns';
            if ($prefix !== '') {
                $attribute .= ':'.$prefix;
            }

            $prevNode->setAttributeNS('http://www.w3.org/2000/xmlns/', $attribute, $uri);
        }

        /** @var PropertyMetadata $propertyMetadata */
        foreach ($properties as $propertyMetadata) {
            $context->pushPropertyMetadata($propertyMetadata);
            $this->visitProperty($propertyMetadata, $data, $context);
            $context->popPropertyMetadata();

            $currentNode = $this->currentNodes;

            if (null === $currentNode) {
                continue;
            } elseif (is_array($currentNode)) {
                $nodes = array_merge($nodes, $currentNode);
            } else {
                $nodes[] = $currentNode;
            }
        }

        return $this->currentNodes = $nodes;
    }

    public function visitProperty(PropertyMetadata $metadata, $data, Context $context)
    {
        $v = $metadata->getValue($data);

        if ($metadata->xmlAttribute) {
            $attributeName = $this->namingStrategy->translateName($metadata);

            $this->currentNodes = $this->document->createElement('tmp');
            $context->accept($v, $metadata->type);

            $node = $this->createAttributeNode($metadata, $attributeName);
            $node->appendChild($this->createTextNode((string) $this->currentNodes->nodeValue));

            return $this->currentNodes = $node;
        }

        if ($metadata->xmlValue) {
            $this->currentNodes = $this->document->createElement('tmp');
            $context->accept($v, $metadata->type);

            $node = $this->currentNodes->childNodes->item(0);
            $this->currentNodes->removeChild($node);

            return $this->currentNodes = $node;
        }

        if ($metadata->xmlAttributeMap) {
            $attributes = [];
            foreach ($v as $key => $value) {
                $node = $this->createAttributeNode($metadata, $key);
                $node->appendChild($this->createTextNode((string) $value));

                $attributes[] = $node;
            }

            return $this->currentNodes = $attributes;
        }

        if (null === $v && ! $context->shouldSerializeNull()) {
            return null;
        }

        $elementName = $this->namingStrategy->translateName($metadata);
        if ('' !== $namespace = (string) $metadata->xmlNamespace) {
            $prevNode = $this->nodeStack->top();
            if (! $prefix = $prevNode->lookupPrefix($namespace)) {
                $prefix = 'ns-'.substr(sha1($namespace), 0, 8);
            }
            $this->currentNodes = $this->document->createElementNS($namespace, $prefix.':'.$elementName);
        } else {
            $this->currentNodes = $this->document->createElement($elementName);
        }

        $context->accept($v, $metadata->type);

        if (is_object($v) && null !== $v && $context->isVisiting($v)) {
            return $this->currentNodes = null;
        }

        if ($metadata->xmlCollectionInline || $metadata->inline) {
            $children = iterator_to_array($this->currentNodes->childNodes);
            foreach ($children as $childNode) {
                $this->currentNodes->removeChild($childNode);
            }

            $this->currentNodes = $children;
        }

        return $this->currentNodes;
    }

    public function visitArray($data, Type $type, Context $context)
    {
        if ($this->nodeStack->count() === 1 && $this->document->documentElement === null) {
            $this->document->appendChild($rootNode = $this->document->createElement('result'));

            $this->nodeStack->pop();
            $this->nodeStack->push($rootNode);
        }

        /** @var PropertyMetadata $metadata */
        $nodeName = 'entry';
        if (($metadata = $this->getCurrentPropertyMetadata($context)) && ! empty($metadata->xmlEntryName)) {
            $nodeName = $metadata->xmlEntryName;
        }

        $attributeName = null !== $metadata ? $metadata->xmlKeyAttribute : null;

        /** @var \DOMNode[] $nodes */
        $nodes = [];
        foreach ($data as $k => $v) {
            $elementName = (null !== $metadata && $metadata->xmlKeyValuePairs && $this->isElementNameValid($k)) ? (string)$k : $nodeName;
            $this->currentNodes = $this->document->createElement($elementName);

            $context->accept($v, $this->getElementType($type));
            if (null !== $attributeName) {
                $this->currentNodes->setAttribute($attributeName, (string)$k);
            }

            $nodes[$k] = $this->currentNodes;
        }

        return $this->currentNodes = array_values($nodes);
    }

    public function visitCustom(callable $handler, $data, Type $type, Context $context)
    {
        $args = func_get_args();

        $handler = array_shift($args);
        array_unshift($args, $this);

        return call_user_func_array($handler, $args);
    }

    public function setNavigator(GraphNavigator $navigator = null)
    {
        $this->currentNodes = $this->document = $this->createDocument();
        $this->nodeStack = new \SplStack();
        $this->attachNullNamespace = false;
        $this->validatedMetadata = [];
    }

    public function startVisiting($data, Type $type, Context $context)
    {
        $this->nodeStack->push($this->currentNodes);
        $this->currentNodes = null;
    }

    public function endVisiting($data, Type $type, Context $context)
    {
        $nodes = $this->currentNodes ?: [];
        $this->currentNodes = $this->nodeStack->pop();
        if ($this->nodeStack->count() === 0 && $this->document->documentElement === null) {
            $rootNode = $this->document->createElement('result');
            $this->document->appendChild($rootNode);

            $this->currentNodes = $rootNode;
        }

        if (! is_array($nodes) && ! $nodes instanceof \DOMNodeList) {
            $this->currentNodes->appendChild($nodes);

            return;
        }

        foreach ($nodes as $node) {
            $this->currentNodes->appendChild($node);
        }
    }

    public function getResult()
    {
        $this->attachNullNS();

        return $this->document->saveXML();
    }

    private function createTextNode($data, $cdata = false)
    {
        if (! $cdata) {
            return $this->document->createTextNode($data);
        }

        return $this->document->createCDATASection($data);
    }

    /**
     * Create a new document object
     *
     * @param null $version
     * @param null $encoding
     *
     * @return \DOMDocument
     */
    private function createDocument($version = null, $encoding = null)
    {
        $doc = new \DOMDocument($version ?: $this->defaultVersion, $encoding ?: $this->defaultEncoding);
        $doc->formatOutput = true;

        return $doc;
    }

    private function attachNullNS()
    {
        if (! $this->attachNullNamespace) {
            return;
        }

        $this->document->documentElement->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:xsi',
            'http://www.w3.org/2001/XMLSchema-instance'
        );
    }

    public function getCurrentPropertyMetadata(Context $context)
    {
        $stack = $context->getMetadataStack();
        if ($stack->count() === 0) {
            return null;
        }

        return $stack->top();
    }

    /**
     * Checks that the name is a valid XML element name.
     *
     * @param string $name
     *
     * @return bool
     */
    private function isElementNameValid($name)
    {
        return $name && preg_match('#^[\pL_][\pL0-9._-]*$#ui', $name);
    }

    /**
     * @param ClassMetadata $metadata
     * @param $properties
     */
    private function validateObjectProperties(ClassMetadata $metadata, $properties)
    {
        $class = $metadata->getName();
        if (isset($this->validatedMetadata[ $class ])) {
            return;
        }

        $has_xml_value = false;
        foreach ($properties as $property) {
            if ($property->xmlValue && !$has_xml_value) {
                $has_xml_value = true;
            } elseif ($property->xmlValue) {
                throw new RuntimeException(sprintf(
                    'Only one property can be target of @XmlValue attribute. Invalid usage detected in class %s',
                    $metadata->getName()
                ));
            }
        }

        if ($has_xml_value) {
            foreach ($properties as $property) {
                if (!$property->xmlValue && !$property->xmlAttribute) {
                    throw new RuntimeException(sprintf(
                        'If you make use of @XmlValue, all other properties in the class must have the @XmlAttribute annotation. Invalid usage detected in class %s.',
                        $metadata->getName()
                    ));
                }
            }
        }

        $this->validatedMetadata[ $class ] = true;
    }

    /**
     * @param PropertyMetadata $metadata
     * @param $attributeName
     *
     * @return \DOMAttr
     */
    private function createAttributeNode(PropertyMetadata $metadata, $attributeName)
    {
        if ('' !== $namespace = (string)$metadata->xmlNamespace) {
            $prevNode = $this->nodeStack->top();
            if (! $prefix = $prevNode->lookupPrefix($namespace)) {
                $prefix = 'ns-'.substr(sha1($namespace), 0, 8);
            }

            $node = $this->document->createAttributeNS($namespace, $prefix.':'.$attributeName);
        } else {
            $node = $this->document->createAttribute($attributeName);
        }

        return $node;
    }
}
