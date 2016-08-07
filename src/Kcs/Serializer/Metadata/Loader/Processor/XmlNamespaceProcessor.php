<?php

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Metadata\ClassMetadata;

class XmlNamespaceProcessor extends ClassMetadataProcessor
{
    protected static function doProcess($annotation, ClassMetadata $metadata)
    {
        $metadata->registerNamespace($annotation->uri, $annotation->prefix);
    }
}
