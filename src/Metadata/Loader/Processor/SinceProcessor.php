<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Metadata\PropertyMetadata;

class SinceProcessor extends PropertyMetadataProcessor
{
    protected static function doProcess($annotation, PropertyMetadata $metadata)
    {
        $metadata->sinceVersion = (string) $annotation->version;
    }
}
