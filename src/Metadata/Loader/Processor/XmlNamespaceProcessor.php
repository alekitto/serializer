<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Metadata\ClassMetadata;

class XmlNamespaceProcessor extends ClassMetadataProcessor
{
    /**
     * {@inheritdoc}
     */
    protected static function doProcess($annotation, ClassMetadata $metadata): void
    {
        $metadata->registerNamespace($annotation->uri, $annotation->prefix);
    }
}
