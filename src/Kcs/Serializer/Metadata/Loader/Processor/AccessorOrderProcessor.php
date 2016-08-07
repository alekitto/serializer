<?php

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Metadata\ClassMetadata;

class AccessorOrderProcessor extends ClassMetadataProcessor
{
    protected static function doProcess($annotation, ClassMetadata $metadata)
    {
        if (is_string($annotation->custom)) {
            $annotation->custom = explode(',', $annotation->custom);
        }

        $metadata->setAccessorOrder($annotation->order, $annotation->custom);
    }
}
