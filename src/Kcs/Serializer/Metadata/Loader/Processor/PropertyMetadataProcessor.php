<?php

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Metadata\MetadataInterface;
use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;

class PropertyMetadataProcessor implements ProcessorInterface
{
    public static function process($annotation, MetadataInterface $metadata)
    {
        if (! $metadata instanceof PropertyMetadata) {
            throw new InvalidArgumentException(static::class." supports PropertyMetadata only");
        }

        static::doProcess($annotation, $metadata);
    }

    protected static function doProcess($annotation, PropertyMetadata $metadata)
    {
        throw new \LogicException("You must implement doProcess method");
    }
}
