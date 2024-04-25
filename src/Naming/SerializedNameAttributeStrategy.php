<?php

declare(strict_types=1);

namespace Kcs\Serializer\Naming;

use Kcs\Serializer\Metadata\PropertyMetadata;

/**
 * Naming strategy which uses an annotation to translate the property name.
 */
final class SerializedNameAttributeStrategy implements PropertyNamingStrategyInterface
{
    private PropertyNamingStrategyInterface $delegate;

    public function __construct(PropertyNamingStrategyInterface $namingStrategy)
    {
        $this->delegate = $namingStrategy;
    }

    public function translateName(PropertyMetadata $property): string
    {
        return $property->serializedName ?: $this->delegate->translateName($property);
    }
}
