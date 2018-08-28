<?php declare(strict_types=1);

namespace Kcs\Serializer\Exclusion;

use Kcs\Serializer\Context;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;

class GroupsExclusionStrategy implements ExclusionStrategyInterface
{
    const DEFAULT_GROUP = 'Default';

    /**
     * @var string[]
     */
    private $groups;

    /**
     * @var bool
     */
    private $nestedGroups;

    public function __construct(array $groups)
    {
        if (empty($groups)) {
            $groups = [self::DEFAULT_GROUP];
        }

        $this->nestedGroups = (function () use (&$groups): bool {
            foreach ($groups as $group) {
                if (is_array($group)) {
                    return true;
                }
            }

            return false;
        })();

        if ($this->nestedGroups) {
            $this->groups = $groups;
        } else {
            $this->groups = array_combine($groups, array_fill(0, count($groups), true));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function shouldSkipClass(ClassMetadata $metadata, Context $navigatorContext): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldSkipProperty(PropertyMetadata $property, Context $navigatorContext): bool
    {
        if ($this->nestedGroups) {
            $groups = $this->getGroupsFor($navigatorContext);

            if (empty($property->groups) && empty($property->exclusionGroups)) {
                return !in_array(self::DEFAULT_GROUP, $groups);
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
        } else {
            if (empty($property->groups) && empty($property->exclusionGroups)) {
                return ! isset($this->groups[self::DEFAULT_GROUP]);
            }

            foreach ($property->exclusionGroups as $group) {
                if (isset($this->groups[$group])) {
                    return true;
                }
            }

            foreach ($property->groups as $group) {
                if (isset($this->groups[$group])) {
                    return false;
                }
            }
        }

        return ! empty($property->groups);
    }

    private function getGroupsFor(Context $navigatorContext): array
    {
        $groups = $this->groups;
        foreach ($navigatorContext->getMetadataStack()->getPath() as $index => $path) {
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
