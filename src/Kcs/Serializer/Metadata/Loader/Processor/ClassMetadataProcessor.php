<?php

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Metadata\MetadataInterface;
use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Metadata\ClassMetadata;

class ClassMetadataProcessor implements ProcessorInterface
{
    public static function process($annotation, MetadataInterface $metadata)
    {
        if (! $metadata instanceof ClassMetadata) {
            throw new InvalidArgumentException(static::class.' supports ClassMetadata only');
        }

        static::doProcess($annotation, $metadata);
    }

    protected static function doProcess($annotation, ClassMetadata $metadata)
    {
        throw new \LogicException('You must implement doProcess method');
    }
}
