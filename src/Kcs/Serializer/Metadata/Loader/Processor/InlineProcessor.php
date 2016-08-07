<?php

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Metadata\PropertyMetadata;

class InlineProcessor extends PropertyMetadataProcessor
{
    protected static function doProcess($annotation, PropertyMetadata $metadata)
    {
        $metadata->inline = true;
    }
}