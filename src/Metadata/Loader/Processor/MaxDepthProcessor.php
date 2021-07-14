<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Metadata\PropertyMetadata;

class MaxDepthProcessor extends PropertyMetadataProcessor
{
    protected static function doProcess(object $annotation, PropertyMetadata $metadata): void
    {
        $metadata->maxDepth = (int) $annotation->depth;
    }
}
