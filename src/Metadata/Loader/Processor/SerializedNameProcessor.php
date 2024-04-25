<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Attribute\SerializedName;
use Kcs\Serializer\Metadata\PropertyMetadata;

use function assert;

class SerializedNameProcessor extends PropertyMetadataProcessor
{
    protected static function doProcess(object $annotation, PropertyMetadata $metadata): void
    {
        assert($annotation instanceof SerializedName);

        $metadata->serializedName = $annotation->name;
    }
}
