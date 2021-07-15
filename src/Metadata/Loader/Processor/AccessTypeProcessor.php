<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Metadata\MetadataInterface;
use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Metadata\ClassMetadata;

use function assert;

class AccessTypeProcessor implements ProcessorInterface
{
    public static function process(object $annotation, MetadataInterface $metadata): void
    {
        assert($annotation instanceof AccessType);
        if (! ($metadata instanceof ClassMetadata)) {
            return;
        }

        $metadata->defaultAccessType = $annotation->type;
    }
}
