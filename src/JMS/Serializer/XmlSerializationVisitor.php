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
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;

/**
 * XmlSerializationVisitor.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class XmlSerializationVisitor extends GenericSerializationVisitor
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
     * @var \DOMNode
     */
    private $currentNode;

    public function visitNull($data, array $type, Context $context)
    {
        $this->attachNullNamespace = true;

        $node = $this->document->createAttribute('xsi:nil');
        $node->value = 'true';
        $this->currentNode = $node;

        return parent::visitNull($data, $type, $context);
    }

    public function visitSimpleString($data, array $type, Context $context)
    {
        $this->currentNode = $this->createTextNode((string) $data);

        return parent::visitString($data, $type, $context);
    }

    public function visitString($data, array $type, Context $context)
    {
        /** @var PropertyMetadata $metadata */
        $metadata = $this->getCurrentPropertyMetadata($context);
        $this->currentNode = $this->createTextNode($data, $metadata ? $metadata->xmlElementCData : true);

        return parent::visitString($data, $type, $context);
    }

    public function visitInteger($data, array $type, Context $context)
    {
        $this->currentNode = $this->createTextNode($data);

        return parent::visitInteger($data, $type, $context);
    }

    public function visitBoolean($data, array $type, Context $context)
    {
        $this->currentNode = $this->createTextNode($data ? 'true' : 'false');

        return parent::visitBoolean($data, $type, $context);
    }

    public function visitDouble($data, array $type, Context $context)
    {
        $this->currentNode = $this->createTextNode($data);

        return parent::visitDouble($data, $type, $context);
    }

    public function visitObject(ClassMetadata $metadata, $data, array $type, Context $context, ObjectConstructorInterface $objectConstructor = null)
    {
        if (isset($metadata->handlerCallbacks[$context->getDirection()][$context->getFormat()])) {
            $callback = $metadata->handlerCallbacks[$context->getDirection()][$context->getFormat()];
            $this->currentNode = $data->$callback($this, null, $context);

            if ($this->nodeStack->count() === 1 && $this->document->documentElement === null) {
                $this->document->appendChild($this->currentNode);
                $this->currentNode = [];
            }

            return $this->getData();
        }

        $properties = $this->getNonSkippedProperties($metadata, $context);
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

        $nodes = array();
        $this->setData(array());

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

            $currentNode = $this->currentNode;

            if (null === $currentNode) {
                continue;
            } elseif (is_array($currentNode)) {
                $nodes = array_merge($nodes, $currentNode);
            } else {
                $nodes[] = $currentNode;
            }
        }

        $this->currentNode = $nodes;
        return $this->getData();
    }

    public function visitProperty(PropertyMetadata $metadata, $data, Context $context)
    {
        $v = $metadata->getValue($data);

        /** @var PropertyMetadata $metadata */
        $metadata = $this->getCurrentPropertyMetadata($context);

        if ($metadata->xmlAttribute) {
            $attributeName = $this->namingStrategy->translateName($metadata);

            $this->currentNode = $this->document->createElement('tmp');
            $this->getNavigator()->accept($v, $metadata->type, $context);

            $node = $this->createAttributeNode($metadata, $attributeName);
            $node->appendChild($this->createTextNode((string) $this->currentNode->nodeValue));

            $this->currentNode = $node;
            return;
        }

        if ($metadata->xmlValue) {
            $this->currentNode = $this->document->createElement('tmp');
            $this->getNavigator()->accept($v, $metadata->type, $context);

            $node = $this->currentNode->childNodes->item(0);
            $this->currentNode->removeChild($node);

            $this->currentNode = $node;
            return;
        }

        if ($metadata->xmlAttributeMap) {
            if (! is_array($v)) {
                throw new RuntimeException(sprintf('Unsupported value type for XML attribute map. Expected array but got %s.', gettype($v)));
            }

            $attributes = [];
            foreach ($v as $key => $value) {
                $node = $this->createAttributeNode($metadata, $key);
                $node->appendChild($this->createTextNode((string) $value));

                $attributes[] = $node;
            }

            $this->currentNode = $attributes;
            return;
        }

        if (null === $v && ! $context->shouldSerializeNull()) {
            return;
        }

        $elementName = $this->namingStrategy->translateName($metadata);
        if ('' !== $namespace = (string) $metadata->xmlNamespace) {
            $prevNode = $this->nodeStack->top();
            if ( ! $prefix = $prevNode->lookupPrefix($namespace)) {
                $prefix = 'ns-'.substr(sha1($namespace), 0, 8);
            }
            $this->currentNode = $this->document->createElementNS($namespace, $prefix.':'.$elementName);
        } else {
            $this->currentNode = $this->document->createElement($elementName);
        }

        parent::visitProperty($metadata, $data, $context);

        if (is_object($v) && null !== $v && $context->isVisiting($v)) {
            $this->currentNode = null;
            return;
        }

        if ($metadata->xmlCollectionInline || $metadata->inline) {
            $children = iterator_to_array($this->currentNode->childNodes);
            foreach ($children as $childNode) {
                $this->currentNode->removeChild($childNode);
            }

            $this->currentNode = $children;
        }
    }

    public function visitArray($data, array $type, Context $context)
    {
        /** @var PropertyMetadata $metadata */
        $nodeName = 'entry';
        if (($metadata = $this->getCurrentPropertyMetadata($context)) && ! empty($metadata->xmlEntryName)) {
            $nodeName = $metadata->xmlEntryName;
        }

        $attributeName = null !== $metadata ? $metadata->xmlKeyAttribute : null;

        $nodes = [];
        $rs = [];
        foreach ($data as $k => $v) {
            $elementName = (null !== $metadata && $metadata->xmlKeyValuePairs && $this->isElementNameValid($k)) ? (string)$k : $nodeName;
            $this->currentNode = $this->document->createElement($elementName);

            $v = $this->getNavigator()->accept($v, $this->getElementType($type), $context);
            if (null !== $attributeName) {
                $this->currentNode->setAttribute($attributeName, (string)$k);
            }

            $nodes[$k] = $this->currentNode;
            $rs[$k] = $v;
        }

        $this->currentNode = array_values($nodes);
        return $rs;
    }

    public function setNavigator(GraphNavigator $navigator = null)
    {
        parent::setNavigator($navigator);

        $this->currentNode = $this->document = $this->createDocument();
        $this->nodeStack = new \SplStack();
        $this->attachNullNamespace = false;
    }

    public function startVisiting($data, array $type, Context $context)
    {
        $this->nodeStack->push($this->currentNode);
        $this->currentNode = null;

        parent::startVisiting($data, $type, $context);
    }

    public function endVisiting($data, array $type, Context $context)
    {
        $nodes = $this->currentNode ?: [];
        $this->currentNode = $this->nodeStack->pop();
        if ($this->nodeStack->count() == 0 && $this->document->documentElement === null) {
            $rootNode = $this->document->createElement('result');
            $this->document->appendChild($rootNode);

            $this->currentNode = $rootNode;
        }

        if (! is_array($nodes) && ! $nodes instanceof \DOMNodeList) {
            $nodes = array($nodes);
        }

        foreach ($nodes as $node) {
            $this->currentNode->appendChild($node);
        }

        return parent::endVisiting($data, $type, $context);
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
    public function createDocument($version = null, $encoding = null)
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
        if ($stack->count() == 0) {
            return null;
        }

        return $stack->top() instanceof PropertyMetadata ? $stack->top() : null;
    }

    /**
     * Checks that the name is a valid XML element name.
     *
     * @param string $name
     *
     * @return boolean
     */
    private function isElementNameValid($name)
    {
        return $name && false === strpos($name, ' ') && preg_match('#^[\pL_][\pL0-9._-]*$#ui', $name);
    }

    /**
     * Get the array of properties that should be serialized
     * in an object
     *
     * @param ClassMetadata $metadata
     * @param Context $context
     *
     * @return PropertyMetadata[]
     */
    private function getNonSkippedProperties(ClassMetadata $metadata, Context $context)
    {
        $properties = $metadata->getAttributesMetadata();
        if (null !== ($exclusionStrategy = $context->getExclusionStrategy())) {
            /** @var PropertyMetadata[] $properties */
            $properties = array_filter(
                $properties,
                function (PropertyMetadata $propertyMetadata) use ($exclusionStrategy, $context) {
                    $context->pushPropertyMetadata($propertyMetadata);
                    $result = !$exclusionStrategy->shouldSkipProperty($propertyMetadata, $context);
                    $context->popPropertyMetadata();

                    return $result;
                }
            );
        }

        return $properties;
    }

    /**
     * @param ClassMetadata $metadata
     * @param $properties
     */
    private function validateObjectProperties(ClassMetadata $metadata, $properties)
    {
        $has_xml_value = false;
        foreach ($properties as $property) {
            if ($property->xmlValue && !$has_xml_value) {
                $has_xml_value = true;
            } elseif ($property->xmlValue) {
                throw new RuntimeException(sprintf(
                    "Only one property can be target of @XmlValue attribute. Invalid usage detected in class %s",
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
                $prefix = 'ns-' . substr(sha1($namespace), 0, 8);
            }

            $node = $this->document->createAttributeNS($namespace, $prefix . ':' . $attributeName);
        } else {
            $node = $this->document->createAttribute($attributeName);
        }

        return $node;
    }
}
