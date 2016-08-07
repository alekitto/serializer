<?php

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Metadata\ClassMetadata;

class DiscriminatorProcessor extends ClassMetadataProcessor
{
    protected static function doProcess($annotation, ClassMetadata $metadata)
    {
        if ($annotation->disabled) {
            $metadata->discriminatorDisabled = true;
        } else {
            $metadata->setDiscriminator($annotation->field, $annotation->map);
        }
    }
}
