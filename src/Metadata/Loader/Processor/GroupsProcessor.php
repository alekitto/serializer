<?php

/*
 * Copyright 2016 Alessandro Chitolina <alekitto@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Metadata\PropertyMetadata;

class GroupsProcessor extends PropertyMetadataProcessor
{
    protected static function doProcess($annotation, PropertyMetadata $metadata)
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
