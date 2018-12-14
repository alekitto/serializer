<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Metadata\ClassMetadata;

class DiscriminatorProcessor extends ClassMetadataProcessor
{
    /**
     * {@inheritdoc}
     */
    protected static function doProcess($annotation, ClassMetadata $metadata): void
    {
        if (\is_string($annotation->groups)) {
            $annotation->groups = \explode(',', $annotation->groups);
        }

        if ($annotation->disabled) {
            $metadata->discriminatorDisabled = true;
        } else {
            $metadata->setDiscriminator($annotation->field, $annotation->map, \array_map('trim', (array) $annotation->groups));
        }
    }
}
