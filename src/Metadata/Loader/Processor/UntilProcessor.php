<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Attribute\Until;
use Kcs\Serializer\Metadata\PropertyMetadata;

use function assert;

class UntilProcessor extends PropertyMetadataProcessor
{
    protected static function doProcess(object $annotation, PropertyMetadata $metadata): void
    {
        assert($annotation instanceof Until);

        $metadata->untilVersion = (string) $annotation->version;
    }
}
