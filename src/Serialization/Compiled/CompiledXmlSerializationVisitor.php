<?php

declare(strict_types=1);

namespace Kcs\Serializer\Serialization\Compiled;

use DOMNode;
use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Context;
use Kcs\Serializer\GraphNavigator;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\XmlSerializationVisitor;

use function array_map;
use function array_merge;
use function assert;
use function is_array;
use function is_object;

class CompiledXmlSerializationVisitor extends XmlSerializationVisitor
{
    private CompiledSerializationPlanFactory|null $compiledPlanFactory = null;
    private CompiledSerializationDescriptorCacheInterface|null $compiledDescriptorCache = null;

    public function setNavigator(GraphNavigator|null $navigator = null): void
    {
        parent::setNavigator($navigator);

        $this->compiledPlanFactory ??= new CompiledSerializationPlanFactory($this->compiledDescriptorCache);
    }

    public function setCompiledSerializationDescriptorCache(CompiledSerializationDescriptorCacheInterface|null $cache): void
    {
        $this->compiledDescriptorCache = $cache;
        $this->compiledPlanFactory = new CompiledSerializationPlanFactory($cache);
    }

    /** @return DOMNode[] */
    public function visitObject(ClassMetadata $metadata, mixed $data, Type $type, Context $context, ObjectConstructorInterface|null $objectConstructor = null): array
    {
        if ($context->getExclusionStrategy() !== null) {
            return parent::visitObject($metadata, $data, $type, $context, $objectConstructor);
        }

        $this->compiledPlanFactory ??= new CompiledSerializationPlanFactory($this->compiledDescriptorCache);
        $plan = $this->compiledPlanFactory->getPlan($metadata, $context);
        $this->validateObjectProperties($metadata, array_map(
            static fn (CompiledPropertyPlan $property): PropertyMetadata => $property->metadata,
            $plan->properties,
        ));

        if ($this->document->documentElement === null && $this->nodeStack->count() === 1) {
            $this->createRootNode($metadata);
        }

        $nodes = [];
        foreach ($metadata->xmlNamespaces as $prefix => $uri) {
            $this->xmlNamespaces[$prefix] = $uri;
        }

        foreach ($plan->properties as $property) {
            assert($property instanceof CompiledPropertyPlan);

            $context->getMetadataStack()->push($property->metadata);
            $value = $property->read($data);
            if (! $this->visitNativePropertyValue($property, $value, $context)) {
                $this->visitPropertyValue($property->metadata, $value, $context, $property->serializedName);
            }

            $context->getMetadataStack()->pop();

            $currentNode = $this->currentNodes;
            if ($currentNode === null) {
                continue;
            }

            $nodes[] = $currentNode;
        }

        return $this->currentNodes = array_merge(...$nodes ?: [[]]);
    }

    /** @return DOMNode[] */
    public function visitHash(mixed $data, Type $type, Context $context): array
    {
        $nativeType = $this->getNativeXmlElementType($type);
        if ($nativeType === null) {
            return parent::visitHash($data, $type, $context);
        }

        if ($this->document->documentElement === null && $this->nodeStack->count() === 1) {
            $this->createRootNode();
        }

        $nodeName = 'entry';
        $metadata = $context->getMetadataStack()->getCurrent();
        if ($metadata !== null && ! empty($metadata->xmlEntryName)) {
            $nodeName = $metadata->xmlEntryName;
        }

        $attributeName = $metadata?->xmlKeyAttribute;
        $namespace = $metadata?->xmlEntryNamespace;
        $cdata = $metadata?->xmlElementCData ?? true; // @phpstan-ignore-line

        $nodes = [];
        foreach ($data as $key => $value) {
            if ($value === null && ! $context->shouldSerializeNull()) {
                continue;
            }

            $elementName = ($metadata === null || $metadata->xmlKeyValuePairs) && $this->isElementNameValid((string) $key) ? (string) $key : $nodeName;
            $node = $this->createElement($namespace, $elementName);
            if ($value !== null) {
                $node->appendChild($this->createTextNode($this->castNativeXmlValue($nativeType, $value), $cdata));
            }

            if ($attributeName !== null) {
                $node->setAttribute($attributeName, (string) $key);
            }

            $nodes[] = $node;
        }

        return $this->currentNodes = $nodes;
    }

    /** @return DOMNode[] */
    public function visitArray(mixed $data, Type $type, Context $context): array
    {
        $nativeType = $this->getNativeXmlElementType($type);
        if ($nativeType === null) {
            return parent::visitArray($data, $type, $context);
        }

        if ($this->document->documentElement === null && $this->nodeStack->count() === 1) {
            $this->createRootNode();
        }

        $nodeName = 'entry';
        $metadata = $context->getMetadataStack()->getCurrent();
        if ($metadata !== null && ! empty($metadata->xmlEntryName)) {
            $nodeName = $metadata->xmlEntryName;
        }

        $namespace = $metadata?->xmlEntryNamespace;
        $cdata = $metadata?->xmlElementCData ?? true; // @phpstan-ignore-line

        $nodes = [];
        foreach ($data as $value) {
            if ($value === null && ! $context->shouldSerializeNull()) {
                continue;
            }

            $node = $this->createElement($namespace, $nodeName);
            if ($value !== null) {
                $node->appendChild($this->createTextNode($this->castNativeXmlValue($nativeType, $value), $cdata));
            }

            $nodes[] = $node;
        }

        return $this->currentNodes = $nodes;
    }

    private function visitNativePropertyValue(CompiledPropertyPlan $property, mixed $value, Context $context): bool
    {
        assert($context instanceof SerializationContext);
        if ($property->nativeType === null || $value === null || is_array($value) || is_object($value)) {
            return false;
        }

        $metadata = $property->metadata;
        if ($metadata->xmlAttributeMap || $metadata->xmlCollectionInline || $metadata->inline) {
            return false;
        }

        $text = $this->castNativeXmlValue($property->nativeType, $value);
        if ($metadata->xmlAttribute) {
            $node = $this->createAttributeNode($metadata, $property->serializedName);
            $node->appendChild($this->createTextNode($text));

            $this->currentNodes = [$node];

            return true;
        }

        if ($metadata->xmlValue) {
            $this->currentNodes = [$this->createTextNode($text, $metadata->xmlElementCData)];

            return true;
        }

        $node = $this->createElement($metadata->xmlNamespace, $property->serializedName);
        $node->appendChild($this->createTextNode($text, $metadata->xmlElementCData));
        $this->currentNodes = [$node];

        return true;
    }

    private function castNativeXmlValue(string $nativeType, mixed $value): string
    {
        return match ($nativeType) {
            'bool' => $value ? 'true' : 'false',
            default => (string) $value,
        };
    }

    private function getNativeXmlElementType(Type $type): string|null
    {
        $elementType = $this->getElementType($type);
        if ($elementType === null || $elementType->countParams() !== 0) {
            return null;
        }

        return match ($elementType->name) {
            'string' => 'string',
            'integer', 'int' => 'int',
            'boolean', 'bool' => 'bool',
            'double', 'float' => 'float',
            default => null,
        };
    }
}
