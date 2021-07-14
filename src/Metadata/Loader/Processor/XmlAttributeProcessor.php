<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Metadata\PropertyMetadata;

use function is_string;

class XmlAttributeProcessor extends PropertyMetadataProcessor
{
    protected static function doProcess(object $annotation, PropertyMetadata $metadata): void
    {
        $metadata->xmlAttribute = true;
        $metadata->xmlNamespace = is_string($annotation->namespace) ? $annotation->namespace : null;
    }
}
