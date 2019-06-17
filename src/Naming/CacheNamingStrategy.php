<?php declare(strict_types=1);

namespace Kcs\Serializer\Naming;

use Kcs\Serializer\Metadata\PropertyMetadata;

final class CacheNamingStrategy implements PropertyNamingStrategyInterface
{
    /**
     * @var PropertyNamingStrategyInterface
     */
    private $delegate;

    /**
     * @var \SplObjectStorage
     */
    private $cache;

    public function __construct(PropertyNamingStrategyInterface $delegate)
    {
        $this->delegate = $delegate;
        $this->cache = new \SplObjectStorage();
    }

    /**
     * {@inheritdoc}
     */
    public function translateName(PropertyMetadata $property): string
    {
        if (isset($this->cache[$property])) {
            return $this->cache[$property];
        }

        return $this->cache[$property] = $this->delegate->translateName($property);
    }
}
