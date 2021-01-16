<?php declare(strict_types=1);

namespace Kcs\Serializer\Naming;

use Kcs\Serializer\Metadata\PropertyMetadata;

/**
 * Generic naming strategy which translates a camel-cased property name.
 */
final class UnderscoreNamingStrategy implements PropertyNamingStrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function translateName(PropertyMetadata $property): string
    {
        return \strtolower(\preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $property->name));
    }
}
