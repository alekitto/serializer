<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Metadata\MetadataInterface;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;

class ReadOnlyProcessor implements ProcessorInterface
{
    public static function process(object $annotation, MetadataInterface $metadata): void
    {
        if ($metadata instanceof ClassMetadata) {
            $metadata->readOnly = true;
        } elseif ($metadata instanceof PropertyMetadata) {
            $metadata->readOnly = $annotation->readOnly;
        }
    }
}
