<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata;

use Kcs\Metadata\ClassMetadataInterface;
use Kcs\Metadata\Factory\AbstractMetadataFactory;

class MetadataFactory extends AbstractMetadataFactory
{
    /**
     * {@inheritdoc}
     */
    protected function createMetadata(\ReflectionClass $class): ClassMetadataInterface
    {
        return new ClassMetadata($class);
    }
}
