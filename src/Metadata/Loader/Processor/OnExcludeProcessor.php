<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Annotation\OnExclude;
use Kcs\Serializer\Metadata\PropertyMetadata;

class OnExcludeProcessor extends PropertyMetadataProcessor
{
    protected static function doProcess($annotation, PropertyMetadata $metadata)
    {
        $metadata->onExclude = OnExclude::NULL === $annotation->policy ? PropertyMetadata::ON_EXCLUDE_NULL : PropertyMetadata::ON_EXCLUDE_SKIP;
    }
}
