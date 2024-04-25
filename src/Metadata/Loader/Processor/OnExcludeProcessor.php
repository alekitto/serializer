<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Attribute\OnExclude;
use Kcs\Serializer\Metadata\PropertyMetadata;

use function assert;

class OnExcludeProcessor extends PropertyMetadataProcessor
{
    protected static function doProcess(object $annotation, PropertyMetadata $metadata): void
    {
        assert($annotation instanceof OnExclude);

        $metadata->onExclude = $annotation->policy;
    }
}
