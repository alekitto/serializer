<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Annotation\ExclusionPolicy;
use Kcs\Serializer\Metadata\ClassMetadata;

use function assert;

class ExclusionPolicyProcessor extends ClassMetadataProcessor
{
    protected static function doProcess(object $annotation, ClassMetadata $metadata): void
    {
        assert($annotation instanceof ExclusionPolicy);

        $metadata->exclusionPolicy = $annotation->policy;
    }
}
