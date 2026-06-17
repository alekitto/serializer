<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata;

use Kcs\Metadata\ClassMetadataInterface;
use Kcs\Metadata\Factory\AbstractMetadataFactory;
use ReflectionClass;

class MetadataFactory extends AbstractMetadataFactory
{
    /** @param ReflectionClass<object> $class */
    protected function createMetadata(ReflectionClass $class): ClassMetadataInterface
    {
        return new ClassMetadata($class);
    }
}
