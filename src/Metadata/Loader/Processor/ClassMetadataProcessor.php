<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Metadata\MetadataInterface;
use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Metadata\ClassMetadata;

class ClassMetadataProcessor implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public static function process($annotation, MetadataInterface $metadata): void
    {
        if (! $metadata instanceof ClassMetadata) {
            throw new InvalidArgumentException(static::class.' supports ClassMetadata only');
        }

        static::doProcess($annotation, $metadata);
    }

    protected static function doProcess($annotation, ClassMetadata $metadata): void
    {
        throw new \LogicException('You must implement doProcess method');
    }
}
