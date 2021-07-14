<?php

declare(strict_types=1);

namespace Kcs\Serializer\Exclusion;

use Kcs\Serializer\Context;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;

class DepthExclusionStrategy implements ExclusionStrategyInterface
{
    public function shouldSkipClass(ClassMetadata $metadata, Context $context): bool
    {
        return $this->isTooDeep($context);
    }

    public function shouldSkipProperty(PropertyMetadata $property, Context $context): bool
    {
        return false;
    }

    private function isTooDeep(Context $context): bool
    {
        $depth = $context->getDepth();
        $metadataStack = $context->getMetadataStack();

        $nthProperty = 1;
        foreach ($metadataStack as $metadata) {
            $relativeDepth = $depth - ++$nthProperty;

            if ($metadata->maxDepth !== null && $relativeDepth > $metadata->maxDepth) {
                return true;
            }
        }

        return false;
    }
}
