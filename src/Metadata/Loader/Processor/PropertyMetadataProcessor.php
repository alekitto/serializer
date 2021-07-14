<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Metadata\MetadataInterface;
use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Metadata\PropertyMetadata;
use LogicException;

class PropertyMetadataProcessor implements ProcessorInterface
{
    public static function process(object $annotation, MetadataInterface $metadata): void
    {
        if (! $metadata instanceof PropertyMetadata) {
            throw new InvalidArgumentException(static::class . ' supports PropertyMetadata only');
        }

        static::doProcess($annotation, $metadata);
    }

    protected static function doProcess(object $annotation, PropertyMetadata $metadata): void
    {
        throw new LogicException('You must implement doProcess method');
    }
}
