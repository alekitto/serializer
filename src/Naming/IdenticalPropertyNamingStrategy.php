<?php

declare(strict_types=1);

namespace Kcs\Serializer\Naming;

use Kcs\Serializer\Metadata\PropertyMetadata;

final class IdenticalPropertyNamingStrategy implements PropertyNamingStrategyInterface
{
    public function translateName(PropertyMetadata $property): string
    {
        return $property->name;
    }
}
