<?php

declare(strict_types=1);

namespace Kcs\Serializer\Naming;

use Kcs\Serializer\Metadata\PropertyMetadata;
use SplObjectStorage;

final class CacheNamingStrategy implements PropertyNamingStrategyInterface
{
    /** @var SplObjectStorage<PropertyMetadata, string> */
    private SplObjectStorage $cache;

    public function __construct(private PropertyNamingStrategyInterface $delegate)
    {
        $this->cache = new SplObjectStorage();
    }

    public function translateName(PropertyMetadata $property): string
    {
        if (isset($this->cache[$property])) {
            return $this->cache[$property];
        }

        return $this->cache[$property] = $this->delegate->translateName($property);
    }
}
