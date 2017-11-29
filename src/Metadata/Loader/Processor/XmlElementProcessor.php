<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Metadata\PropertyMetadata;

class XmlElementProcessor extends PropertyMetadataProcessor
{
    protected static function doProcess($annotation, PropertyMetadata $metadata)
    {
        $metadata->xmlAttribute = false;
        $metadata->xmlElementCData = $annotation->cdata;
        $metadata->xmlNamespace = $annotation->namespace;
    }
}
