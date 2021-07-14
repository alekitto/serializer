<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Metadata\ClassMetadata;

class XmlNamespaceProcessor extends ClassMetadataProcessor
{
    protected static function doProcess(object $annotation, ClassMetadata $metadata): void
    {
        $metadata->registerNamespace($annotation->uri, $annotation->prefix);
    }
}
