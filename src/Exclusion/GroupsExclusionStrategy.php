<?php

declare(strict_types=1);

namespace Kcs\Serializer\Exclusion;

use Kcs\Serializer\Context;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;

use function array_fill;
use function array_filter;
use function array_key_exists;
use function array_values;
use function count;
use function in_array;
use function is_array;
use function Safe\array_combine;
use function strpos;

class GroupsExclusionStrategy implements ExclusionStrategyInterface
{
    public const DEFAULT_GROUP = 'Default';

    /** @var array<string, mixed> */
    private array $groups;
    private bool $nestedGroups;

    /**
     * @param array<string|int, mixed> $groups
     */
    public function __construct(array $groups)
    {
        if (empty($groups)) {
            $groups = [self::DEFAULT_GROUP];
        }

        $this->nestedGroups = (static function () use (&$groups): bool {
            foreach ($groups as $group) {
                if (is_array($group)) {
                    return true;
                }
            }

            return false;
        })();

        if ($this->nestedGroups) {
            $this->groups = $groups; // @phpstan-ignore-line
        } else {
            $this->groups = array_combine($groups, array_fill(0, count($groups), true));
        }
    }

    public function shouldSkipClass(ClassMetadata $metadata, Context $navigatorContext): bool
    {
        return false;
    }

    public function shouldSkipProperty(PropertyMetadata $property, Context $navigatorContext): bool
    {
        if ($this->nestedGroups) {
            $groups = $this->getGroupsFor($navigatorContext);

            if (empty($property->groups) && empty($property->exclusionGroups)) {
                return ! in_array(self::DEFAULT_GROUP, $groups, true);
            }

            foreach ($property->exclusionGroups as $group) {
                if (in_array($group, $groups, true)) {
                    return true;
                }
            }

            foreach ($property->groups as $group) {
                if (in_array($group, $groups, true)) {
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

    /**
     * @return string[]
     */
    private function getGroupsFor(Context $navigatorContext): array
    {
        $groups = $this->groups;
        $metadataPath = array_values(array_filter(
            $navigatorContext->getMetadataStack()->getPath(),
            static function ($path) {
                return strpos((string) $path, '[') !== 0;
            }
        ));

        foreach ($metadataPath as $index => $path) {
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
