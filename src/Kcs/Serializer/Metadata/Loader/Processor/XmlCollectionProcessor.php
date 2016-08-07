<?php

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Annotation\XmlMap;
use Kcs\Serializer\Metadata\PropertyMetadata;

class XmlCollectionProcessor extends PropertyMetadataProcessor
{
    protected static function doProcess($annotation, PropertyMetadata $metadata)
    {
        $metadata->xmlCollection = true;
        $metadata->xmlCollectionInline = $annotation->inline;
        $metadata->xmlEntryName = $annotation->entry;
        $metadata->xmlEntryNamespace = $annotation->namespace;

        if ($annotation instanceof XmlMap) {
            $metadata->xmlKeyAttribute = $annotation->keyAttribute;
        }
    }
}
