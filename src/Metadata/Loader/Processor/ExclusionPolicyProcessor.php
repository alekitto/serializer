<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Metadata\ClassMetadata;

class ExclusionPolicyProcessor extends ClassMetadataProcessor
{
    protected static function doProcess(object $annotation, ClassMetadata $metadata): void
    {
        $metadata->exclusionPolicy = $annotation->policy;
    }
}
