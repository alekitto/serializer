<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Metadata\MetadataInterface;
use Kcs\Serializer\Annotation\Immutable;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;

use function assert;

class ImmutableProcessor implements ProcessorInterface
{
    public static function process(object $annotation, MetadataInterface $metadata): void
    {
        assert($annotation instanceof Immutable);

        if ($metadata instanceof ClassMetadata) {
            $metadata->immutable = true;
        } elseif ($metadata instanceof PropertyMetadata) {
            $metadata->immutable = $annotation->immutable;
        }
    }
}
