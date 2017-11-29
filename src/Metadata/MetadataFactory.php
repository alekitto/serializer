<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata;

use Kcs\Metadata\ClassMetadataInterface;
use Kcs\Metadata\Factory\AbstractMetadataFactory;

/**
 * @author Alessandro Chitolina <alekitto@gmail.com>
 */
class MetadataFactory extends AbstractMetadataFactory
{
    protected function createMetadata(\ReflectionClass $class): ClassMetadataInterface
    {
        return new ClassMetadata($class);
    }
}
