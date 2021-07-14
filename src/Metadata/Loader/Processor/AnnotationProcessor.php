<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Metadata\MetadataInterface;
use Kcs\Serializer\Annotation as Annotations;

use function array_key_exists;
use function get_class;

class AnnotationProcessor
{
    /** @var ProcessorInterface[] */
    protected static array $processor = [
        Annotations\AccessType::class => AccessTypeProcessor::class,
        Annotations\ReadOnly::class => ReadOnlyProcessor::class,

        /* Class annotations */
        Annotations\AccessorOrder::class => AccessorOrderProcessor::class,
        Annotations\Discriminator::class => DiscriminatorProcessor::class,
        Annotations\ExclusionPolicy::class => ExclusionPolicyProcessor::class,
        Annotations\XmlNamespace::class => XmlNamespaceProcessor::class,
        Annotations\XmlRoot::class => XmlRootProcessor::class,
        Annotations\Xml\XmlNamespace::class => XmlNamespaceProcessor::class,
        Annotations\Xml\Root::class => XmlRootProcessor::class,

        /* Property annotations */
        Annotations\Since::class => SinceProcessor::class,
        Annotations\Until::class => UntilProcessor::class,
        Annotations\SerializedName::class => SerializedNameProcessor::class,
        Annotations\Type::class => TypeProcessor::class,
        Annotations\XmlElement::class => XmlElementProcessor::class,
        Annotations\XmlList::class => XmlCollectionProcessor::class,
        Annotations\XmlMap::class => XmlCollectionProcessor::class,
        Annotations\XmlKeyValuePairs::class => XmlKeyValuePairsProcessor::class,
        Annotations\XmlAttribute::class => XmlAttributeProcessor::class,
        Annotations\XmlAttributeMap::class => XmlAttributeMapProcessor::class,
        Annotations\XmlValue::class => XmlValueProcessor::class,
        Annotations\Xml\Element::class => XmlElementProcessor::class,
        Annotations\Xml\XmlList::class => XmlCollectionProcessor::class,
        Annotations\Xml\Map::class => XmlCollectionProcessor::class,
        Annotations\Xml\KeyValuePairs::class => XmlKeyValuePairsProcessor::class,
        Annotations\Xml\Attribute::class => XmlAttributeProcessor::class,
        Annotations\Xml\AttributeMap::class => XmlAttributeMapProcessor::class,
        Annotations\Xml\Value::class => XmlValueProcessor::class,
        Annotations\Csv::class => CsvProcessor::class,
        Annotations\Groups::class => GroupsProcessor::class,
        Annotations\Inline::class => InlineProcessor::class,
        Annotations\MaxDepth::class => MaxDepthProcessor::class,
        Annotations\OnExclude::class => OnExcludeProcessor::class,
    ];

    public function process(object $annotation, MetadataInterface $metadata): void
    {
        $class = get_class($annotation);
        if (! array_key_exists($class, static::$processor)) {
            return;
        }

        $processor = static::$processor[$class];
        $processor::process($annotation, $metadata);
    }
}
