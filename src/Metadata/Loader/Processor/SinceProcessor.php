<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Metadata\PropertyMetadata;

class SinceProcessor extends PropertyMetadataProcessor
{
    /**
     * {@inheritdoc}
     */
    protected static function doProcess($annotation, PropertyMetadata $metadata): void
    {
        $metadata->sinceVersion = (string) $annotation->version;
    }
}
