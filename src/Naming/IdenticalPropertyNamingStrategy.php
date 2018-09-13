<?php declare(strict_types=1);

namespace Kcs\Serializer\Naming;

use Kcs\Serializer\Metadata\PropertyMetadata;

class IdenticalPropertyNamingStrategy implements PropertyNamingStrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function translateName(PropertyMetadata $property): string
    {
        return $property->name;
    }
}
