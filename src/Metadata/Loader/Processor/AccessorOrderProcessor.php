<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Annotation\AccessorOrder;
use Kcs\Serializer\Metadata\ClassMetadata;

use function array_map;
use function assert;
use function explode;
use function is_string;

class AccessorOrderProcessor extends ClassMetadataProcessor
{
    protected static function doProcess(object $annotation, ClassMetadata $metadata): void
    {
        assert($annotation instanceof AccessorOrder);

        if (is_string($annotation->custom)) {
            $annotation->custom = explode(',', $annotation->custom);
        }

        $metadata->setAccessorOrder($annotation->order, array_map('trim', (array) $annotation->custom));
    }
}
