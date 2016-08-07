<?php

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Metadata\PropertyMetadata;

class TypeProcessor extends PropertyMetadataProcessor
{
    protected static function doProcess($annotation, PropertyMetadata $metadata)
    {
        $metadata->setType($annotation->name);
    }
}
