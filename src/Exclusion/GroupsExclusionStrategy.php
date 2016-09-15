<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
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

namespace Kcs\Serializer\Exclusion;

use Kcs\Serializer\Context;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;

class GroupsExclusionStrategy implements ExclusionStrategyInterface
{
    const DEFAULT_GROUP = 'Default';

    private $groups = [];

    public function __construct(array $groups)
    {
        if (empty($groups)) {
            $groups = [self::DEFAULT_GROUP];
        }

        $this->groups = $groups;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldSkipClass(ClassMetadata $metadata, Context $navigatorContext)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldSkipProperty(PropertyMetadata $property, Context $navigatorContext)
    {
        $groups = $this->getGroupsFor($navigatorContext);

        if (empty($property->groups)) {
            return ! in_array(self::DEFAULT_GROUP, $groups);
        }

        foreach ($property->exclusionGroups as $group) {
            if (in_array($group, $groups)) {
                return true;
            }
        }

        foreach ($property->groups as $group) {
            if (in_array($group, $groups)) {
                return false;
            }
        }

        return true;
    }

    private function getGroupsFor(Context $navigatorContext)
    {
        $groups = $this->groups;
        foreach ($navigatorContext->getCurrentPath() as $index => $path) {
            if (! array_key_exists($path, $groups)) {
                if ($index > 0) {
                    return [self::DEFAULT_GROUP];
                }

                break;
            }

            $groups = $groups[$path];
        }

        return $groups;
    }
}
