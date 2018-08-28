<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Metadata\PropertyMetadata;

class XmlValueProcessor extends PropertyMetadataProcessor
{
    /**
     * {@inheritdoc}
     */
    protected static function doProcess($annotation, PropertyMetadata $metadata): void
    {
        $metadata->xmlValue = true;
        $metadata->xmlElementCData = $annotation->cdata;
    }
}
