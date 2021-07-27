<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Metadata\MetadataInterface;
use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Metadata\ClassMetadata;
use LogicException;

class ClassMetadataProcessor implements ProcessorInterface
{
    public static function process(object $annotation, MetadataInterface $metadata): void
    {
        if (! $metadata instanceof ClassMetadata) {
            throw new InvalidArgumentException(static::class . ' supports ClassMetadata only');
        }

        static::doProcess($annotation, $metadata);
    }

    /**
     * Process annotation/attribute
     */
    protected static function doProcess(object $annotation, ClassMetadata $metadata): void
    {
        throw new LogicException('You must implement doProcess method');
    }
}
