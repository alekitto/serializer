<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Metadata\ClassMetadata;

class XmlRootProcessor extends ClassMetadataProcessor
{
    /**
     * {@inheritdoc}
     */
    protected static function doProcess($annotation, ClassMetadata $metadata): void
    {
        $metadata->xmlRootName = $annotation->name;
        $metadata->xmlRootNamespace = $annotation->namespace;
        $metadata->xmlEncoding = null !== $annotation->encoding ? \strtoupper($annotation->encoding) : null;
    }
}
