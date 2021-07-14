<?php

declare(strict_types=1);

namespace Kcs\Serializer\Exclusion;

use Kcs\Serializer\Context;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Stringable;

use function version_compare;

class VersionExclusionStrategy implements ExclusionStrategyInterface
{
    private string $version;

    /**
     * @param string|Stringable $version
     */
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
        $version = $property->sinceVersion;
        if ($version !== null && version_compare($this->version, $version, '<')) {
            return true;
        }

        $version = $property->untilVersion;

        return $version !== null && version_compare($this->version, $version, '>');
    }
}
