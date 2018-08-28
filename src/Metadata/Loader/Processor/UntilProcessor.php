<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Metadata\PropertyMetadata;

class UntilProcessor extends PropertyMetadataProcessor
{
    /**
     * {@inheritdoc}
     */
    protected static function doProcess($annotation, PropertyMetadata $metadata): void
    {
        $metadata->untilVersion = (string) $annotation->version;
    }
}
