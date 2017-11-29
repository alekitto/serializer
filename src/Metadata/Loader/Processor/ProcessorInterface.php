<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Metadata\MetadataInterface;

interface ProcessorInterface
{
    public static function process($annotation, MetadataInterface $metadata);
}
