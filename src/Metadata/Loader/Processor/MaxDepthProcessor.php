<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Annotation\MaxDepth;
use Kcs\Serializer\Metadata\PropertyMetadata;

use function assert;

class MaxDepthProcessor extends PropertyMetadataProcessor
{
    protected static function doProcess(object $annotation, PropertyMetadata $metadata): void
    {
        assert($annotation instanceof MaxDepth);

        $metadata->maxDepth = (int) $annotation->depth;
    }
}
