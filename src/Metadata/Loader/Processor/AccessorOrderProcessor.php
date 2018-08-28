<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Metadata\ClassMetadata;

class AccessorOrderProcessor extends ClassMetadataProcessor
{
    /**
     * {@inheritdoc}
     */
    protected static function doProcess($annotation, ClassMetadata $metadata): void
    {
        if (is_string($annotation->custom)) {
            $annotation->custom = explode(',', $annotation->custom);
        }

        $metadata->setAccessorOrder($annotation->order, array_map('trim', (array) $annotation->custom));
    }
}
