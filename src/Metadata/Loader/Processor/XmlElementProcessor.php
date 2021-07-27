<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Annotation\Xml\Element;
use Kcs\Serializer\Metadata\PropertyMetadata;

use function assert;

class XmlElementProcessor extends PropertyMetadataProcessor
{
    protected static function doProcess(object $annotation, PropertyMetadata $metadata): void
    {
        assert($annotation instanceof Element);

        $metadata->xmlAttribute = false;
        $metadata->xmlElementCData = $annotation->cdata;
        $metadata->xmlNamespace = $annotation->namespace;
    }
}
