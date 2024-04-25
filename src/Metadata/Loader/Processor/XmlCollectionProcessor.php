<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Attribute\Xml\Collection;
use Kcs\Serializer\Attribute\Xml\Map;
use Kcs\Serializer\Metadata\PropertyMetadata;

use function assert;

class XmlCollectionProcessor extends PropertyMetadataProcessor
{
    protected static function doProcess(object $annotation, PropertyMetadata $metadata): void
    {
        assert($annotation instanceof Collection);

        $metadata->xmlCollection = true;
        $metadata->xmlCollectionInline = $annotation->inline;
        $metadata->xmlEntryName = $annotation->entry;
        $metadata->xmlEntryNamespace = $annotation->namespace;

        if (! ($annotation instanceof Map)) {
            return;
        }

        $metadata->xmlKeyAttribute = $annotation->keyAttribute;
    }
}
