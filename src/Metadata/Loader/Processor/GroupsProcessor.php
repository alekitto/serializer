<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Metadata\PropertyMetadata;

class GroupsProcessor extends PropertyMetadataProcessor
{
    /**
     * {@inheritdoc}
     */
    protected static function doProcess($annotation, PropertyMetadata $metadata): void
    {
        if (is_string($annotation->groups)) {
            $annotation->groups = explode(',', $annotation->groups);
        }

        $groups = $excludeGroups = [];
        $annotation->groups = array_map('trim', (array) $annotation->groups);
        foreach ($annotation->groups as $group) {
            if (false !== strpos($group, ',')) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid group name "%s" on "%s", did you mean to create multiple groups?',
                    implode(', ', $annotation->groups),
                    $metadata->class.'->'.$metadata->name
                ));
            }

            if ('!' === $group[0]) {
                $excludeGroups[] = substr($group, 1);
            } else {
                $groups[] = $group;
            }
        }

        $metadata->groups = $groups;
        $metadata->exclusionGroups = $excludeGroups;
    }
}
