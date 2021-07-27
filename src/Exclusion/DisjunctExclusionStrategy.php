<?php

declare(strict_types=1);

namespace Kcs\Serializer\Exclusion;

use Kcs\Serializer\Context;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;

use function assert;

/**
 * Disjunct Exclusion Strategy.
 *
 * This strategy is short-circuiting and will skip a class, or property as soon as one of the delegates skips it.
 */
class DisjunctExclusionStrategy implements ExclusionStrategyInterface
{
    /** @var ExclusionStrategyInterface[] */
    private array $delegates;

    /**
     * @param ExclusionStrategyInterface[] $delegates
     */
    public function __construct(array $delegates)
    {
        $this->delegates = $delegates;
    }

    public function addStrategy(ExclusionStrategyInterface $strategy): void
    {
        $this->delegates[] = $strategy;
    }

    public function shouldSkipClass(ClassMetadata $metadata, Context $context): bool
    {
        foreach ($this->delegates as $delegate) {
            assert($delegate instanceof ExclusionStrategyInterface);
            if ($delegate->shouldSkipClass($metadata, $context)) {
                return true;
            }
        }

        return false;
    }

    public function shouldSkipProperty(PropertyMetadata $property, Context $context): bool
    {
        foreach ($this->delegates as $delegate) {
            if ($delegate->shouldSkipProperty($property, $context)) {
                return true;
            }
        }

        return false;
    }
}
