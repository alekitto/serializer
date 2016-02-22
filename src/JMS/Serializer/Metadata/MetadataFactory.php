<?php

namespace JMS\Serializer\Metadata;

use Kcs\Metadata\ClassMetadataInterface;
use Kcs\Metadata\Exception\InvalidMetadataException;
use Kcs\Metadata\Factory\AbstractMetadataFactory;

/**
 * @author Alessandro Chitolina <alekitto@gmail.com>
 */
class MetadataFactory extends AbstractMetadataFactory
{
    protected function createMetadata(\ReflectionClass $class)
    {
        return new ClassMetadata($class);
    }
}
