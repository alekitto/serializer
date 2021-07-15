<?php

declare(strict_types=1);

namespace Kcs\Serializer\Naming;

use Kcs\Serializer\Metadata\PropertyMetadata;

use function Safe\preg_replace;
use function strtolower;

/**
 * Generic naming strategy which translates a camel-cased property name.
 */
final class UnderscoreNamingStrategy implements PropertyNamingStrategyInterface
{
    public function translateName(PropertyMetadata $property): string
    {
        return strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $property->name));
    }
}
