<?php

declare(strict_types=1);

namespace Kcs\Serializer\Exclusion;

use Kcs\Serializer\Context;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;

use function version_compare;

class VersionExclusionStrategy implements ExclusionStrategyInterface
{
    private string $version;

    public function __construct($version)
    {
        $this->version = (string) $version;
    }

    public function shouldSkipClass(ClassMetadata $metadata, Context $navigatorContext): bool
    {
        return false;
    }

    public function shouldSkipProperty(PropertyMetadata $property, Context $navigatorContext): bool
    {
        if ((null !== $version = $property->sinceVersion) && version_compare($this->version, $version, '<')) {
            return true;
        }

        return (null !== $version = $property->untilVersion) && version_compare($this->version, $version, '>');
    }
}
