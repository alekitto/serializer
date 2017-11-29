<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Metadata\ClassMetadata;

class XmlRootProcessor extends ClassMetadataProcessor
{
    protected static function doProcess($annotation, ClassMetadata $metadata)
    {
        $metadata->xmlRootName = $annotation->name;
        $metadata->xmlRootNamespace = $annotation->namespace;
    }
}
