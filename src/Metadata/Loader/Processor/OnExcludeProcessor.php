<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Annotation\OnExclude;
use Kcs\Serializer\Metadata\PropertyMetadata;

class OnExcludeProcessor extends PropertyMetadataProcessor
{
    protected static function doProcess(object $annotation, PropertyMetadata $metadata): void
    {
        $metadata->onExclude = $annotation->policy === OnExclude::NULL
            ? PropertyMetadata::ON_EXCLUDE_NULL
            : PropertyMetadata::ON_EXCLUDE_SKIP;
    }
}
