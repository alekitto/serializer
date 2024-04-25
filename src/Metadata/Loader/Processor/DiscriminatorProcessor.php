<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Annotation\Discriminator;
use Kcs\Serializer\Metadata\ClassMetadata;

use function array_map;
use function assert;

class DiscriminatorProcessor extends ClassMetadataProcessor
{
    protected static function doProcess(object $annotation, ClassMetadata $metadata): void
    {
        assert($annotation instanceof Discriminator);

        if ($annotation->disabled) {
            $metadata->discriminatorDisabled = true;
        } else {
            $metadata->setDiscriminator($annotation->field, $annotation->map, array_map('trim', (array) $annotation->groups));
        }
    }
}
