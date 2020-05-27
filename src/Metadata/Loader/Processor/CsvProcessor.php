<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader\Processor;

use Kcs\Serializer\Annotation\Csv;
use Kcs\Serializer\Metadata\ClassMetadata;

class CsvProcessor extends ClassMetadataProcessor
{
    /**
     * {@inheritdoc}
     */
    protected static function doProcess($annotation, ClassMetadata $metadata): void
    {
        assert($annotation instanceof Csv);

        $metadata->csvDelimiter = $annotation->delimiter ?? ',';
        $metadata->csvEnclosure = $annotation->enclosure ?? '"';
        $metadata->csvEscapeChar = $annotation->escapeChar ?? '';
        $metadata->csvEscapeFormulas = $annotation->escapeFormulas ?? false;
        $metadata->csvKeySeparator = $annotation->keySeparator ?? '.';
        $metadata->csvNoHeaders = ! ($annotation->printHeaders ?? true);
        $metadata->csvOutputBom = $annotation->outputBom ?? false;
    }
}
