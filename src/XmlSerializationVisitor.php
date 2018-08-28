<?php declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Metadata\AdditionalPropertyMetadata;
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

    /**
     * @var string
     */
    private $defaultVersion = '1.0';

    /**
     * @var string
     */
    private $defaultEncoding = 'UTF-8';

    /**
     * @var bool
     */
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
     * @var string[]
     */
    private $xmlNamespaces = [];

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function visitString($data, Type $type, Context $context)
    {
        /** @var PropertyMetadata $metadata */
        $metadata = $context->getMetadataStack()->getCurrent();

        return $this->currentNodes = $this->createTextNode($data, $metadata ? $metadata->xmlElementCData : true);
    }

    /**
     * {@inheritdoc}
     */
    public function visitInteger($data, Type $type, Context $context)
    {
        return $this->currentNodes = $this->createTextNode($data);
    }

    /**
     * {@inheritdoc}
     */
    public function visitBoolean($data, Type $type, Context $context)
    {
        return $this->currentNodes = $this->createTextNode($data ? 'true' : 'false');
    }

    /**
     * {@inheritdoc}
     */
    public function visitDouble($data, Type $type, Context $context)
    {
        return $this->currentNodes = $this->createTextNode($data);
    }

    /**
     * {@inheritdoc}
     */
    public function visitObject(
        ClassMetadata $metadata,
        $data,
        Type $type,
        Context $context,
        ?ObjectConstructorInterface $objectConstructor = null
    ) {
        $properties = $context->getNonSkippedProperties($metadata);
        $this->validateObjectProperties($metadata, $properties);

        if (1 === $this->nodeStack->count() && null === $this->document->documentElement) {
            $this->createRootNode($metadata);
        }

        $nodes = [];

        foreach ($metadata->xmlNamespaces as $prefix => $uri) {
            $this->xmlNamespaces[$prefix] = $uri;
        }

        /** @var PropertyMetadata $propertyMetadata */
        foreach ($properties as $propertyMetadata) {
            $context->getMetadataStack()->push($propertyMetadata);
            $this->visitProperty($propertyMetadata, $data, $context);
            $context->getMetadataStack()->pop();

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

    protected function visitProperty(PropertyMetadata $metadata, $data, Context $context)
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
            return $this->currentNodes = null;
        }

        $elementName = $this->namingStrategy->translateName($metadata);
        $this->currentNodes = $this->createElement($metadata->xmlNamespace, $elementName);

        $context->accept($v, $metadata->type);

        if (is_object($v) && null !== $v &&
            ! $metadata instanceof AdditionalPropertyMetadata &&
            $context->isVisiting($v)) {
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

    /**
     * {@inheritdoc}
     */
    public function visitArray($data, Type $type, Context $context)
    {
        if (1 === $this->nodeStack->count() && null === $this->document->documentElement) {
            $this->createRootNode();
        }

        /** @var PropertyMetadata $metadata */
        $nodeName = 'entry';
        if (($metadata = $context->getMetadataStack()->getCurrent()) && ! empty($metadata->xmlEntryName)) {
            $nodeName = $metadata->xmlEntryName;
        }

        $attributeName = null !== $metadata ? $metadata->xmlKeyAttribute : null;
        $namespace = null !== $metadata ? $metadata->xmlEntryNamespace : null;

        /** @var \DOMNode[] $nodes */
        $nodes = [];
        $elementType = $this->getElementType($type);
        foreach ($data as $k => $v) {
            $elementName = (null !== $metadata && $metadata->xmlKeyValuePairs && $this->isElementNameValid($k)) ? (string) $k : $nodeName;
            $this->currentNodes = $this->createElement($namespace, $elementName);

            $context->accept($v, $elementType);
            if (null !== $attributeName) {
                $this->currentNodes->setAttribute($attributeName, (string) $k);
            }

            $nodes[] = $this->currentNodes;
        }

        return $this->currentNodes = $nodes;
    }

    /**
     * {@inheritdoc}
     */
    public function setNavigator(?GraphNavigator $navigator = null): void
    {
        $this->currentNodes = $this->document = $this->createDocument();
        $this->nodeStack = new \SplStack();
        $this->attachNullNamespace = false;
    }

    /**
     * {@inheritdoc}
     */
    public function startVisiting($data, Type $type, Context $context): void
    {
        $this->nodeStack->push($this->currentNodes);
        $this->currentNodes = null;
    }

    /**
     * {@inheritdoc}
     */
    public function endVisiting($data, Type $type, Context $context)
    {
        $nodes = $this->currentNodes ?: [];
        $this->currentNodes = $this->nodeStack->pop();
        if (0 === $this->nodeStack->count() && null === $this->document->documentElement) {
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

    /**
     * {@inheritdoc}
     */
    public function getResult()
    {
        $this->attachNullNS();

        foreach ($this->xmlNamespaces as $prefix => $uri) {
            $attribute = 'xmlns';
            if ('' !== $prefix) {
                $attribute .= ':'.$prefix;
            }

            $this->document->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', $attribute, $uri);
        }

        return $this->document->saveXML();
    }

    private function createTextNode($data, bool $cdata = false)
    {
        if (! $cdata) {
            return $this->document->createTextNode((string) $data);
        }

        return $this->document->createCDATASection($data);
    }

    /**
     * Create a new document object.
     *
     * @param string|null $version
     * @param string|null $encoding
     *
     * @return \DOMDocument
     */
    private function createDocument(?string $version = null, ?string $encoding = null): \DOMDocument
    {
        $doc = new \DOMDocument($version ?: $this->defaultVersion, $encoding ?: $this->defaultEncoding);
        $doc->formatOutput = true;

        return $doc;
    }

    private function attachNullNS(): void
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

    /**
     * Checks that the name is a valid XML element name.
     *
     * @param string $name
     *
     * @return bool
     */
    private function isElementNameValid($name): bool
    {
        return $name && preg_match('#^[\pL_][\pL0-9._-]*$#ui', (string) $name);
    }

    /**
     * @param ClassMetadata $metadata
     * @param array         $properties
     */
    private function validateObjectProperties(ClassMetadata $metadata, array $properties): void
    {
        $hasXmlValue = false;
        foreach ($properties as $property) {
            if ($property->xmlValue && ! $hasXmlValue) {
                $hasXmlValue = true;
            } elseif ($property->xmlValue) {
                throw new RuntimeException(sprintf(
                    'Only one property can be target of @XmlValue attribute. Invalid usage detected in class %s',
                    $metadata->getName()
                ));
            }
        }

        if ($hasXmlValue) {
            foreach ($properties as $property) {
                if (! $property->xmlValue && ! $property->xmlAttribute) {
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
     * @param string           $attributeName
     *
     * @return \DOMAttr
     */
    private function createAttributeNode(PropertyMetadata $metadata, string $attributeName): \DOMAttr
    {
        if ($namespace = (string) $metadata->xmlNamespace) {
            $prefix = $this->lookupPrefix($namespace);
            $node = $this->document->createAttributeNS($namespace, $prefix.':'.$attributeName);
        } else {
            $node = $this->document->createAttribute($attributeName);
        }

        return $node;
    }

    private function createElement(?string $namespace, ?string $elementName): \DOMElement
    {
        if (! empty($namespace) && $prefix = $this->lookupPrefix($namespace)) {
            $node = $this->document->createElement($prefix.':'.$elementName);
        } else {
            $node = $this->document->createElement($elementName);
        }

        return $node;
    }

    private function lookupPrefix(string $namespace): string
    {
        if (false !== ($prefix = array_search($namespace, $this->xmlNamespaces))) {
            return $prefix;
        }

        $prefix = 'ns-'.substr(sha1($namespace), 0, 8);
        $this->xmlNamespaces[$prefix] = $namespace;

        return $prefix;
    }

    /**
     * @param ClassMetadata $metadata
     */
    private function createRootNode(?ClassMetadata $metadata = null): void
    {
        $rootName = null !== $metadata && $metadata->xmlRootName ? $metadata->xmlRootName : 'result';
        if (null !== $metadata && ($rootNamespace = $metadata->xmlRootNamespace)) {
            $rootNode = $this->document->createElementNS($rootNamespace, $rootName);
        } else {
            $rootNode = $this->document->createElement($rootName);
        }

        $this->document->appendChild($rootNode);

        $this->nodeStack->pop();
        $this->nodeStack->push($rootNode);
    }
}
