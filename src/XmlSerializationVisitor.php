<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use DOMAttr;
use DOMCdataSection;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Metadata\AdditionalPropertyMetadata;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Type\Type;
use SplStack;
use Stringable;

use function array_merge;
use function array_search;
use function assert;
use function is_object;
use function iterator_to_array;
use function Safe\preg_match;
use function Safe\sprintf;
use function Safe\substr;
use function sha1;

/**
 * XmlSerializationVisitor.
 */
class XmlSerializationVisitor extends AbstractVisitor
{
    public DOMDocument $document;

    private string $defaultVersion = '1.0';
    private string $defaultEncoding = 'UTF-8';
    private bool $attachNullNamespace = false;
    private SplStack $nodeStack;

    /** @var DOMNode[] */
    private ?array $currentNodes = null;

    /** @var string[] */
    private array $xmlNamespaces = [];

    /**
     * Sets the default document encoding.
     */
    public function setDefaultEncoding(string $defaultEncoding): void
    {
        $this->defaultEncoding = $defaultEncoding;
    }

    /**
     * {@inheritdoc}
     */
    public function visitNull($data, Type $type, Context $context)
    {
        $this->attachNullNamespace = true;

        $node = $this->document->createAttribute('xsi:nil');
        $node->value = 'true';

        return $this->currentNodes = [$node];
    }

    /**
     * @param string|Stringable $data
     *
     * @return DOMNode[]
     */
    public function visitSimpleString($data): array
    {
        return $this->currentNodes = [$this->createTextNode((string) $data)];
    }

    /**
     * {@inheritdoc}
     */
    public function visitString($data, Type $type, Context $context)
    {
        $metadata = $context->getMetadataStack()->getCurrent();

        return $this->currentNodes = [$this->createTextNode($data, $metadata->xmlElementCData ?? true)];
    }

    /**
     * {@inheritdoc}
     */
    public function visitInteger($data, Type $type, Context $context)
    {
        return $this->currentNodes = [$this->createTextNode($data)];
    }

    /**
     * {@inheritdoc}
     */
    public function visitBoolean($data, Type $type, Context $context)
    {
        return $this->currentNodes = [$this->createTextNode($data ? 'true' : 'false')];
    }

    /**
     * {@inheritdoc}
     */
    public function visitDouble($data, Type $type, Context $context)
    {
        return $this->currentNodes = [$this->createTextNode($data)];
    }

    /**
     * {@inheritdoc}
     */
    public function visitObject(ClassMetadata $metadata, $data, Type $type, Context $context, ?ObjectConstructorInterface $objectConstructor = null)
    {
        $properties = $context->getNonSkippedProperties($metadata);
        $this->validateObjectProperties($metadata, $properties);

        if ($this->document->documentElement === null && $this->nodeStack->count() === 1) {
            $this->createRootNode($metadata);
        }

        $nodes = [];

        foreach ($metadata->xmlNamespaces as $prefix => $uri) {
            $this->xmlNamespaces[$prefix] = $uri;
        }

        foreach ($properties as $propertyMetadata) {
            assert($propertyMetadata instanceof PropertyMetadata);
            $context->getMetadataStack()->push($propertyMetadata);
            $this->visitProperty($propertyMetadata, $data, $context);
            $context->getMetadataStack()->pop();

            $currentNode = $this->currentNodes;
            if ($currentNode === null) {
                continue;
            }

            $nodes[] = $currentNode;
        }

        return $this->currentNodes = array_merge(...$nodes ?: [[]]);
    }

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    protected function visitProperty(PropertyMetadata $metadata, $data, Context $context)
    {
        assert($context instanceof SerializationContext);
        $v = $metadata->getValue($data);

        if ($v === null && ! $context->shouldSerializeNull()) {
            return $this->currentNodes = null;
        }

        if ($metadata->xmlAttribute) {
            // Do not attach null namespace on null attributes
            $attachNull = $this->attachNullNamespace;
            $attributeName = $this->namingStrategy->translateName($metadata);

            $this->currentNodes = [$this->document->createElement('tmp')];
            $context->accept($v, $metadata->type);

            $node = $this->createAttributeNode($metadata, $attributeName);
            $node->appendChild($this->createTextNode((string) $this->currentNodes[0]->nodeValue));

            $this->attachNullNamespace = $attachNull;

            return $this->currentNodes = [$node];
        }

        if ($metadata->xmlValue) {
            $this->currentNodes = [$this->document->createElement('tmp')];
            $context->accept($v, $metadata->type);

            $node = $this->currentNodes[0]->childNodes->item(0);
            $this->currentNodes[0]->removeChild($node);

            return $this->currentNodes = [$node];
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

        $elementName = $this->namingStrategy->translateName($metadata);
        $this->currentNodes = [$this->createElement($metadata->xmlNamespace, $elementName)];

        $context->accept($v, $metadata->type);

        if (
            is_object($v) && $v !== null &&
            ! $metadata instanceof AdditionalPropertyMetadata &&
            $context->isVisiting($v)
        ) {
            return $this->currentNodes = null;
        }

        if ($metadata->xmlCollectionInline || $metadata->inline) {
            $children = iterator_to_array($this->currentNodes[0]->childNodes);
            foreach ($children as $childNode) {
                $this->currentNodes[0]->removeChild($childNode);
            }

            $this->currentNodes = $children;
        }

        return $this->currentNodes;
    }

    /**
     * {@inheritdoc}
     */
    public function visitHash($data, Type $type, Context $context)
    {
        if ($this->document->documentElement === null && $this->nodeStack->count() === 1) {
            $this->createRootNode();
        }

        $nodeName = 'entry';
        $metadata = $context->getMetadataStack()->getCurrent();
        if ($metadata !== null && ! empty($metadata->xmlEntryName)) {
            assert($metadata instanceof PropertyMetadata);
            $nodeName = $metadata->xmlEntryName;
        }

        $attributeName = $metadata !== null ? $metadata->xmlKeyAttribute : null;
        $namespace = $metadata !== null ? $metadata->xmlEntryNamespace : null;

        /** @var DOMNode[] $nodes */
        $nodes = [];
        $elementType = $this->getElementType($type);
        foreach ($data as $k => $v) {
            $elementName = ($metadata === null || $metadata->xmlKeyValuePairs) && $this->isElementNameValid($k) ? (string) $k : $nodeName;
            $this->currentNodes = [$this->createElement($namespace, $elementName)];

            $context->getMetadataStack()->pushIndexPath((string) $k);
            $context->accept($v, $elementType);
            $context->getMetadataStack()->popIndexPath();

            if ($attributeName !== null) {
                $this->currentNodes[0]->setAttribute($attributeName, (string) $k);
            }

            $nodes[] = $this->currentNodes[0];
        }

        return $this->currentNodes = $nodes;
    }

    /**
     * {@inheritdoc}
     */
    public function visitArray($data, Type $type, Context $context)
    {
        if ($this->document->documentElement === null && $this->nodeStack->count() === 1) {
            $this->createRootNode();
        }

        $nodeName = 'entry';
        $metadata = $context->getMetadataStack()->getCurrent();
        if ($metadata !== null && ! empty($metadata->xmlEntryName)) {
            assert($metadata instanceof PropertyMetadata);
            $nodeName = $metadata->xmlEntryName;
        }

        $namespace = $metadata !== null ? $metadata->xmlEntryNamespace : null;

        /** @var DOMNode[] $nodes */
        $nodes = [];
        $elementType = $this->getElementType($type);
        foreach ($data as $k => $v) {
            $this->currentNodes = [$this->createElement($namespace, $nodeName)];

            $context->getMetadataStack()->pushIndexPath((string) $k);
            $context->accept($v, $elementType);
            $context->getMetadataStack()->popIndexPath();

            $nodes[] = $this->currentNodes[0];
        }

        return $this->currentNodes = $nodes;
    }

    public function setNavigator(?GraphNavigator $navigator = null): void
    {
        $this->currentNodes = [$this->document = $this->createDocument()];
        $this->nodeStack = new SplStack();
        $this->attachNullNamespace = false;
    }

    /**
     * {@inheritdoc}
     */
    public function startVisiting(&$data, Type $type, Context $context): void
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
        if ($this->document->documentElement === null && $this->nodeStack->count() === 0) {
            $rootNode = $this->document->createElement('result');
            $this->document->appendChild($rootNode);

            $this->currentNodes = [$rootNode];
        }

        foreach ($nodes as $node) {
            $this->currentNodes[0]->appendChild($node);
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
            if ($prefix !== '') {
                $attribute .= ':' . $prefix;
            }

            $this->document->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', $attribute, $uri);
        }

        return $this->document->saveXML();
    }

    /**
     * @param string|Stringable $data
     *
     * @return DOMCdataSection|DOMText
     */
    private function createTextNode($data, bool $cdata = false)
    {
        $data = (string) $data;
        if (! $cdata) {
            return $this->document->createTextNode($data);
        }

        return $this->document->createCDATASection($data);
    }

    /**
     * Create a new document object.
     */
    private function createDocument(?string $version = null, ?string $encoding = null): DOMDocument
    {
        $doc = new DOMDocument($version ?: $this->defaultVersion, $encoding ?: $this->defaultEncoding);
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
     * @param string|Stringable $name
     */
    private function isElementNameValid($name): bool
    {
        return $name && preg_match('#^[\pL_][\pL0-9._-]*$#ui', (string) $name);
    }

    /**
     * @param PropertyMetadata[] $properties
     */
    private function validateObjectProperties(ClassMetadata $metadata, array $properties): void
    {
        $hasXmlValue = false;
        foreach ($properties as $property) {
            if ($property->xmlValue && ! $hasXmlValue) {
                $hasXmlValue = true;
            } elseif ($property->xmlValue) {
                throw new RuntimeException(sprintf('Only one property can be target of @XmlValue attribute. Invalid usage detected in class %s', $metadata->getName()));
            }
        }

        if (! $hasXmlValue) {
            return;
        }

        foreach ($properties as $property) {
            if (! $property->xmlValue && ! $property->xmlAttribute) {
                throw new RuntimeException(sprintf('If you make use of @XmlValue, all other properties in the class must have the @XmlAttribute annotation. Invalid usage detected in class %s.', $metadata->getName()));
            }
        }
    }

    private function createAttributeNode(PropertyMetadata $metadata, string $attributeName): DOMAttr
    {
        $namespace = (string) $metadata->xmlNamespace;
        if ($namespace !== '') {
            $prefix = $this->lookupPrefix($namespace);
            $node = $this->document->createAttributeNS($namespace, $prefix . ':' . $attributeName);
        } else {
            $node = $this->document->createAttribute($attributeName);
        }

        return $node;
    }

    private function createElement(?string $namespace, ?string $elementName): DOMElement
    {
        $prefix = $namespace !== null ? $this->lookupPrefix($namespace) : '';
        if ($prefix !== '') {
            $node = $this->document->createElement($prefix . ':' . $elementName);
        } else {
            $node = $this->document->createElement($elementName ?? '');
        }

        return $node;
    }

    private function lookupPrefix(string $namespace): string
    {
        $prefix = array_search($namespace, $this->xmlNamespaces, true);
        if ($prefix !== false) {
            return (string) $prefix;
        }

        $prefix = 'ns-' . substr(sha1($namespace), 0, 8);
        $this->xmlNamespaces[$prefix] = $namespace;

        return $prefix;
    }

    private function createRootNode(?ClassMetadata $metadata = null): void
    {
        $rootName = $metadata !== null && $metadata->xmlRootName ? $metadata->xmlRootName : 'result';
        $rootNamespace = $metadata !== null ? $metadata->xmlRootNamespace : null;
        if ($rootNamespace !== null) {
            $rootNode = $this->document->createElementNS($rootNamespace, $rootName);
        } else {
            $rootNode = $this->document->createElement($rootName);
        }

        if ($metadata !== null && $metadata->xmlEncoding !== null) {
            $this->document->encoding = $metadata->xmlEncoding;
        }

        $this->document->appendChild($rootNode);

        $this->nodeStack->pop();
        $this->nodeStack->push([$rootNode]);
    }
}
