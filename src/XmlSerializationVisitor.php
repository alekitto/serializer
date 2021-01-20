<?php declare(strict_types=1);

namespace Kcs\Serializer;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;
use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Metadata\AdditionalPropertyMetadata;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Type\Type;
use SplStack;

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

    public function visitSimpleString($data): array
    {
        return $this->currentNodes = [$this->createTextNode((string) $data)];
    }

    /**
     * {@inheritdoc}
     */
    public function visitString($data, Type $type, Context $context)
    {
        /** @var PropertyMetadata $metadata */
        $metadata = $context->getMetadataStack()->getCurrent();

        return $this->currentNodes = [$this->createTextNode($data, $metadata ? $metadata->xmlElementCData : true)];
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

        if (null === $this->document->documentElement && 1 === $this->nodeStack->count()) {
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
            }

            $nodes[] = $currentNode;
        }

        return $this->currentNodes = \array_merge(...$nodes ?: [[]]);
    }

    protected function visitProperty(PropertyMetadata $metadata, $data, Context $context)
    {
        $v = $metadata->getValue($data);

        if (null === $v && ! $context->shouldSerializeNull()) {
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

        if (\is_object($v) && null !== $v &&
            ! $metadata instanceof AdditionalPropertyMetadata &&
            $context->isVisiting($v)) {
            return $this->currentNodes = null;
        }

        if ($metadata->xmlCollectionInline || $metadata->inline) {
            $children = \iterator_to_array($this->currentNodes[0]->childNodes);
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
        if (null === $this->document->documentElement && 1 === $this->nodeStack->count()) {
            $this->createRootNode();
        }

        /** @var PropertyMetadata $metadata */
        $nodeName = 'entry';
        if (($metadata = $context->getMetadataStack()->getCurrent()) && ! empty($metadata->xmlEntryName)) {
            $nodeName = $metadata->xmlEntryName;
        }

        $attributeName = null !== $metadata ? $metadata->xmlKeyAttribute : null;
        $namespace = null !== $metadata ? $metadata->xmlEntryNamespace : null;

        /** @var DOMNode[] $nodes */
        $nodes = [];
        $elementType = $this->getElementType($type);
        foreach ($data as $k => $v) {
            $elementName = ((null === $metadata || $metadata->xmlKeyValuePairs) && $this->isElementNameValid($k)) ? (string) $k : $nodeName;
            $this->currentNodes = [$this->createElement($namespace, $elementName)];

            $context->accept($v, $elementType);
            if (null !== $attributeName) {
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
        if (null === $this->document->documentElement && 1 === $this->nodeStack->count()) {
            $this->createRootNode();
        }

        /** @var PropertyMetadata $metadata */
        $nodeName = 'entry';
        if (($metadata = $context->getMetadataStack()->getCurrent()) && ! empty($metadata->xmlEntryName)) {
            $nodeName = $metadata->xmlEntryName;
        }

        $namespace = null !== $metadata ? $metadata->xmlEntryNamespace : null;

        /** @var DOMNode[] $nodes */
        $nodes = [];
        $elementType = $this->getElementType($type);
        foreach ($data as $v) {
            $this->currentNodes = [$this->createElement($namespace, $nodeName)];
            $context->accept($v, $elementType);

            $nodes[] = $this->currentNodes[0];
        }

        return $this->currentNodes = $nodes;
    }

    /**
     * {@inheritdoc}
     */
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
        if (null === $this->document->documentElement && 0 === $this->nodeStack->count()) {
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
            if ('' !== $prefix) {
                $attribute .= ':'.$prefix;
            }

            $this->document->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', $attribute, $uri);
        }

        return $this->document->saveXML();
    }

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
     */
    private function isElementNameValid($name): bool
    {
        return $name && \preg_match('#^[\pL_][\pL0-9._-]*$#ui', (string) $name);
    }

    private function validateObjectProperties(ClassMetadata $metadata, array $properties): void
    {
        $hasXmlValue = false;
        foreach ($properties as $property) {
            if ($property->xmlValue && ! $hasXmlValue) {
                $hasXmlValue = true;
            } elseif ($property->xmlValue) {
                throw new RuntimeException(\sprintf('Only one property can be target of @XmlValue attribute. Invalid usage detected in class %s', $metadata->getName()));
            }
        }

        if ($hasXmlValue) {
            foreach ($properties as $property) {
                if (! $property->xmlValue && ! $property->xmlAttribute) {
                    throw new RuntimeException(\sprintf('If you make use of @XmlValue, all other properties in the class must have the @XmlAttribute annotation. Invalid usage detected in class %s.', $metadata->getName()));
                }
            }
        }
    }

    private function createAttributeNode(PropertyMetadata $metadata, string $attributeName): DOMAttr
    {
        if ($namespace = (string) $metadata->xmlNamespace) {
            $prefix = $this->lookupPrefix($namespace);
            $node = $this->document->createAttributeNS($namespace, $prefix.':'.$attributeName);
        } else {
            $node = $this->document->createAttribute($attributeName);
        }

        return $node;
    }

    private function createElement(?string $namespace, ?string $elementName): DOMElement
    {
        if (null !== $namespace && $prefix = $this->lookupPrefix($namespace)) {
            $node = $this->document->createElement($prefix.':'.$elementName);
        } else {
            $node = $this->document->createElement($elementName);
        }

        return $node;
    }

    private function lookupPrefix(string $namespace): string
    {
        if (false !== ($prefix = \array_search($namespace, $this->xmlNamespaces))) {
            return $prefix;
        }

        $prefix = 'ns-'.\substr(\sha1($namespace), 0, 8);
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

        if (null !== $metadata && null !== $metadata->xmlEncoding) {
            $this->document->encoding = $metadata->xmlEncoding;
        }

        $this->document->appendChild($rootNode);

        $this->nodeStack->pop();
        $this->nodeStack->push([$rootNode]);
    }
}
