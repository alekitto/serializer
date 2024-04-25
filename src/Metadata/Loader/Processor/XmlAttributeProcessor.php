<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Attribute\Xml\Attribute;
use Kcs\Serializer\Metadata\PropertyMetadata;

use function assert;
use function is_string;

class XmlAttributeProcessor extends PropertyMetadataProcessor
{
    protected static function doProcess(object $annotation, PropertyMetadata $metadata): void
    {
        assert($annotation instanceof Attribute);

        $metadata->xmlAttribute = true;
        $metadata->xmlNamespace = is_string($annotation->namespace) ? $annotation->namespace : null;
    }
}
