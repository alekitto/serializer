<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Attribute\Since;
use Kcs\Serializer\Metadata\PropertyMetadata;

use function assert;

class SinceProcessor extends PropertyMetadataProcessor
{
    protected static function doProcess(object $annotation, PropertyMetadata $metadata): void
    {
        assert($annotation instanceof Since);

        $metadata->sinceVersion = (string) $annotation->version;
    }
}
