<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Metadata\MetadataInterface;
use Kcs\Serializer\Annotation\ReadOnly;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;

use function assert;

class ReadOnlyProcessor implements ProcessorInterface
{
    public static function process(object $annotation, MetadataInterface $metadata): void
    {
        assert($annotation instanceof ReadOnly);

        if ($metadata instanceof ClassMetadata) {
            $metadata->readOnly = true;
        } elseif ($metadata instanceof PropertyMetadata) {
            $metadata->readOnly = $annotation->readOnly;
        }
    }
}
