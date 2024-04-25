<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Metadata\PropertyMetadata;

use function assert;

class TypeProcessor extends PropertyMetadataProcessor
{
    protected static function doProcess(object $annotation, PropertyMetadata $metadata): void
    {
        assert($annotation instanceof Type);

        $metadata->setType($annotation->name);
    }
}
