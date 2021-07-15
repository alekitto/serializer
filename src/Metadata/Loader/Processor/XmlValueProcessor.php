<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Annotation\Xml\Value;
use Kcs\Serializer\Metadata\PropertyMetadata;

use function assert;

class XmlValueProcessor extends PropertyMetadataProcessor
{
    protected static function doProcess(object $annotation, PropertyMetadata $metadata): void
    {
        assert($annotation instanceof Value);

        $metadata->xmlValue = true;
        $metadata->xmlElementCData = $annotation->cdata;
    }
}
