<?php declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;

class CsvSerializationVisitor extends GenericSerializationVisitor
{
    private const FORMULAS_START_CHARS = ['=', '-', '+', '@'];
    private const UTF8_BOM = "\xEF\xBB\xBF";
    private ?ClassMetadata $rootMetadata = null;

    /**
     * {@inheritdoc}
     */
    public function visitObject(ClassMetadata $metadata, $data, Type $type, Context $context, ?ObjectConstructorInterface $objectConstructor = null)
    {
        if (null === $this->rootMetadata) {
            $this->rootMetadata = $metadata;
        }

        return parent::visitObject($metadata, $data, $type, $context, $objectConstructor);
    }

    /**
     * {@inheritdoc}
     */
    public function setNavigator(?GraphNavigator $navigator = null): void
    {
        $this->rootMetadata = null;
        parent::setNavigator($navigator);
    }

    /**
     * {@inheritdoc}
     */
    public function getResult(): string
    {
        $handle = \fopen('php://temp,', 'w+');
        $data = $this->getRoot();

        if (! \is_iterable($data)) {
            $data = [[$data]];
        } elseif (empty($data)) {
            $data = [[]];
        } else {
            $data = \is_array($data) ? $data : \iterator_to_array($data);

            // Sequential arrays of arrays are considered as collections
            if (
                \array_keys($data) !== \array_keys(\array_values($data)) ||
                0 < \count(\array_filter(\array_map('gettype', $data), static fn (string $t) => 'array' !== $t))
            ) {
                $data = [$data];
            }
        }

        [$delimiter, $keySeparator, $escapeFormulas, $enclosure, $escapeChar, $noHeaders, $outputBom] = $this->getOptions();

        foreach ($data as &$value) {
            $flattened = [];
            $this->flatten($value, $flattened, $keySeparator, '', $escapeFormulas);
            $value = $flattened;
        }
        unset($value);

        $headers = $this->extractHeaders($data);

        if (! $noHeaders) {
            \fputcsv($handle, $headers, $delimiter, $enclosure, $escapeChar);
        }

        $headers = \array_fill_keys($headers, '');
        foreach ($data as $row) {
            \fputcsv($handle, \array_replace($headers, $row), $delimiter, $enclosure, $escapeChar);
        }

        \rewind($handle);
        $value = \stream_get_contents($handle);
        \fclose($handle);

        if ($outputBom) {
            if (! \preg_match('//u', $value)) {
                throw new RuntimeException('You are trying to add a UTF-8 BOM to a non UTF-8 text.');
            }

            $value = self::UTF8_BOM.$value;
        }

        return $value;
    }

    /**
     * Flattens an array and generates keys including the path.
     */
    private function flatten(iterable $array, array &$result, string $keySeparator, string $parentKey = '', bool $escapeFormulas = false): void
    {
        foreach ($array as $key => $value) {
            if (\is_iterable($value)) {
                $this->flatten($value, $result, $keySeparator, $parentKey.$key.$keySeparator, $escapeFormulas);
            } elseif ($escapeFormulas && \in_array(((string) $value)[0], self::FORMULAS_START_CHARS, true)) {
                $result[$parentKey.$key] = "\t".$value;
            } else {
                // Ensures an actual value is used when dealing with true and false
                if (false === $value) {
                    $value = 0;
                } elseif (true === $value) {
                    $value = 1;
                }

                $result[$parentKey.$key] = $value;
            }
        }
    }

    /**
     * @return string[]
     */
    private function extractHeaders(iterable $data): array
    {
        $headers = [];
        foreach ($data as $row) {
            $headers += \array_flip(\array_keys($row));
        }

        return \array_keys($headers);
    }

    private function getOptions(): array
    {
        if (null === $this->rootMetadata) {
            return [',', '.', false, '"', '', false, false];
        }

        return [
            $this->rootMetadata->csvDelimiter,
            $this->rootMetadata->csvKeySeparator,
            $this->rootMetadata->csvEscapeFormulas,
            $this->rootMetadata->csvEnclosure,
            $this->rootMetadata->csvEscapeChar,
            $this->rootMetadata->csvNoHeaders,
            $this->rootMetadata->csvOutputBom,
        ];
    }
}