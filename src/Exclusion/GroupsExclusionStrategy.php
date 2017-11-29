<?php declare(strict_types=1);

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
