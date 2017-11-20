<?php declare(strict_types=1);

/*
 * Copyright 2017 Alessandro Chitolina <alekitto@gmail.com>
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

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Metadata\MetadataInterface;
use Kcs\Serializer\Annotation as Annotations;
use Kcs\Serializer\Exception\InvalidArgumentException;

class AnnotationProcessor
{
    /**
     * @var ProcessorInterface[]
     */
    protected static $processor = [
        Annotations\AccessType::class => AccessTypeProcessor::class,
        Annotations\ReadOnly::class => ReadOnlyProcessor::class,

        /* Class annotations */
        Annotations\AccessorOrder::class => AccessorOrderProcessor::class,
        Annotations\Discriminator::class => DiscriminatorProcessor::class,
        Annotations\ExclusionPolicy::class => ExclusionPolicyProcessor::class,
        Annotations\XmlNamespace::class => XmlNamespaceProcessor::class,
        Annotations\XmlRoot::class => XmlRootProcessor::class,

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
        Annotations\Groups::class => GroupsProcessor::class,
        Annotations\Inline::class => InlineProcessor::class,
        Annotations\MaxDepth::class => MaxDepthProcessor::class,
    ];

    public function process($annotation, MetadataInterface $metadata)
    {
        if (! is_object($annotation)) {
            throw new InvalidArgumentException('You must pass an annotation object as first parameter of process');
        }

        $class = get_class($annotation);
        if (! array_key_exists($class, static::$processor)) {
            return;
        }

        $processor = static::$processor[$class];
        $processor::process($annotation, $metadata);
    }
}
