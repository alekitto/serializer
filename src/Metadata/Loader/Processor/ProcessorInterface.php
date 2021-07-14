<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Metadata\MetadataInterface;

interface ProcessorInterface
{
    /**
     * Process attribute/annotation and update the metadata accordingly.
     */
    public static function process(object $annotation, MetadataInterface $metadata): void;
}
