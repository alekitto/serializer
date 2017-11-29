<?php declare(strict_types=1);

namespace Kcs\Serializer\Exclusion;

use Kcs\Serializer\Context;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;

/**
 * @author Adrien Brault <adrien.brault@gmail.com>
 */
class DepthExclusionStrategy implements ExclusionStrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function shouldSkipClass(ClassMetadata $metadata, Context $context)
    {
        return $this->isTooDeep($context);
    }

    /**
     * {@inheritdoc}
     */
    public function shouldSkipProperty(PropertyMetadata $property, Context $context)
    {
        return false;
    }

    private function isTooDeep(Context $context)
    {
        $depth = $context->getDepth();
        $metadataStack = $context->getMetadataStack();

        $nthProperty = 1;
        foreach ($metadataStack as $metadata) {
            $relativeDepth = $depth - ++$nthProperty;

            if (null !== $metadata->maxDepth && $relativeDepth > $metadata->maxDepth) {
                return true;
            }
        }

        return false;
    }
}
