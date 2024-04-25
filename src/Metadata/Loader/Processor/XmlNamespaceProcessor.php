<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Attribute\Xml\XmlNamespace;
use Kcs\Serializer\Metadata\ClassMetadata;

use function assert;

class XmlNamespaceProcessor extends ClassMetadataProcessor
{
    protected static function doProcess(object $annotation, ClassMetadata $metadata): void
    {
        assert($annotation instanceof XmlNamespace);

        $metadata->registerNamespace($annotation->uri, $annotation->prefix);
    }
}
