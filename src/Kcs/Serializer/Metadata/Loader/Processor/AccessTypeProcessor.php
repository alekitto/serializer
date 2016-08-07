<?php

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Metadata\MetadataInterface;
use Kcs\Serializer\Metadata\ClassMetadata;

class AccessTypeProcessor implements ProcessorInterface
{
    public static function process($annotation, MetadataInterface $metadata)
    {
        if ($metadata instanceof ClassMetadata) {
            $metadata->defaultAccessType = $annotation->type;
        }
    }
}
