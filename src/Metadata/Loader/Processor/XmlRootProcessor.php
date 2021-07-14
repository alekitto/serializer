<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Metadata\ClassMetadata;

use function strtoupper;

class XmlRootProcessor extends ClassMetadataProcessor
{
    protected static function doProcess(object $annotation, ClassMetadata $metadata): void
    {
        $metadata->xmlRootName = $annotation->name;
        $metadata->xmlRootNamespace = $annotation->namespace;
        $metadata->xmlEncoding = $annotation->encoding !== null ? strtoupper($annotation->encoding) : null;
    }
}
