<?php

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Metadata\PropertyMetadata;

class GroupsProcessor extends PropertyMetadataProcessor
{
    protected static function doProcess($annotation, PropertyMetadata $metadata)
    {
        if (is_string($annotation->groups)) {
            $annotation->groups = array_map('trim', explode(',', $annotation->groups));
        }

        $metadata->groups = (array)$annotation->groups;
        foreach ($metadata->groups as $groupName) {
            if (false !== strpos($groupName, ',')) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid group name "%s" on "%s", did you mean to create multiple groups?',
                    implode(', ', $metadata->groups),
                    $metadata->class.'->'.$metadata->name
                ));
            }
        }
    }
}
