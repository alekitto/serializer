<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Annotation\Groups;
use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Metadata\PropertyMetadata;

use function array_map;
use function assert;
use function implode;
use function Safe\sprintf;
use function Safe\substr;
use function strpos;

class GroupsProcessor extends PropertyMetadataProcessor
{
    protected static function doProcess(object $annotation, PropertyMetadata $metadata): void
    {
        assert($annotation instanceof Groups);

        $groups = $excludeGroups = [];
        $annotation->groups = array_map('trim', (array) $annotation->groups);
        foreach ($annotation->groups as $group) {
            if (strpos($group, ',') !== false) {
                throw new InvalidArgumentException(sprintf('Invalid group name "%s" on "%s", did you mean to create multiple groups?', implode(', ', $annotation->groups), $metadata->class . '->' . $metadata->name));
            }

            if ($group[0] === '!') {
                $excludeGroups[] = substr($group, 1);
            } else {
                $groups[] = $group;
            }
        }

        $metadata->groups = $groups;
        $metadata->exclusionGroups = $excludeGroups;
    }
}
