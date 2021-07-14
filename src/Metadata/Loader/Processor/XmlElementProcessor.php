<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Metadata\PropertyMetadata;

class XmlElementProcessor extends PropertyMetadataProcessor
{
    protected static function doProcess(object $annotation, PropertyMetadata $metadata): void
    {
        $metadata->xmlAttribute = false;
        $metadata->xmlElementCData = $annotation->cdata;
        $metadata->xmlNamespace = $annotation->namespace;
    }
}
