<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;

use function array_fill_keys;
use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function count;
use function in_array;
use function is_array;
use function is_iterable;
use function iterator_to_array;
use function Safe\array_flip;
use function Safe\array_replace;
use function Safe\fclose;
use function Safe\fopen;
use function Safe\fputcsv;
use function Safe\preg_match;
use function Safe\rewind;
use function Safe\stream_get_contents;
use function str_replace;

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
        if ($this->rootMetadata === null) {
            $this->rootMetadata = $metadata;
        }

        return parent::visitObject($metadata, $data, $type, $context, $objectConstructor);
    }

    public function setNavigator(?GraphNavigator $navigator = null): void
    {
        $this->rootMetadata = null;
        parent::setNavigator($navigator);
    }

    /**
     * @param resource $handle
     * @param mixed[] $data
     */
    private static function fputcsv($handle, array $data, string $delimiter, string $enclosure, string $escapeChar): int
    {
        $data = array_map(static fn (?string $value) => null === $value ? $value : str_replace($enclosure, $escapeChar . $enclosure, $value), $data);

        return fputcsv($handle, $data, $delimiter, $enclosure, $enclosure);
    }

    public function getResult(): string
    {
        $handle = fopen('php://temp,', 'w+');
        $data = $this->getRoot();

        [$delimiter, $keySeparator, $escapeFormulas, $enclosure, $escapeChar, $noHeaders, $outputBom] = $this->getOptions();
        [$headers, $data] = $this->prepareData($data, $keySeparator, $escapeFormulas);

        if (! $noHeaders) {
            self::fputcsv($handle, $headers, $delimiter, $enclosure, $escapeChar);
        }

        $headers = array_fill_keys($headers, '');
        foreach ($data as $row) {
            self::fputcsv($handle, array_replace($headers, $row), $delimiter, $enclosure, $escapeChar);
        }

        rewind($handle);
        $value = stream_get_contents($handle);
        fclose($handle);

        if ($outputBom) {
            if (! preg_match('//u', $value)) {
                throw new RuntimeException('You are trying to add a UTF-8 BOM to a non UTF-8 text.');
            }

            $value = self::UTF8_BOM . $value;
        }

        return $value;
    }

    /**
     * Prepares the data to be written as csv (or other tabular format).
     *
     * @param mixed $data
     *
     * @phpstan-return array{0: (int|string)[], 1: array<string, mixed>[]}
     *
     * @retrun mixed[]
     */
    final protected function prepareData($data, string $keySeparator, bool $escapeFormulas): array
    {
        if (! is_iterable($data)) {
            $data = [[$data]];
        } elseif (empty($data)) {
            $data = [[]];
        } else {
            $data = is_array($data) ? $data : iterator_to_array($data);

            // Sequential arrays of arrays are considered as collections
            if (
                array_keys($data) !== array_keys(array_values($data)) ||
                0 < count(array_filter(array_map('gettype', $data), static fn (string $t) => 'array' !== $t))
            ) {
                $data = [$data];
            }
        }

        foreach ($data as &$value) {
            $flattened = [];
            $this->flatten($value, $flattened, $keySeparator, '', $escapeFormulas);
            $value = $flattened;
        }

        unset($value);

        $headers = $this->extractHeaders($data);

        return [$headers, $data];
    }

    /**
     * Flattens an array and generates keys including the path.
     *
     * @param iterable<string|int, mixed> $array
     * @param array<string, mixed> $result
     */
    private function flatten(iterable $array, array &$result, string $keySeparator, string $parentKey = '', bool $escapeFormulas = false): void
    {
        foreach ($array as $key => $value) {
            if (is_iterable($value)) {
                $this->flatten($value, $result, $keySeparator, $parentKey . $key . $keySeparator, $escapeFormulas);
            } elseif ($escapeFormulas && in_array(((string) $value)[0], self::FORMULAS_START_CHARS, true)) {
                $result[$parentKey . $key] = "\t" . $value;
            } else {
                // Ensures an actual value is used when dealing with true and false
                if ($value === false) {
                    $value = 0;
                } elseif ($value === true) {
                    $value = 1;
                }

                $result[$parentKey . $key] = $value;
            }
        }
    }

    /**
     * @param iterable<string|int, mixed> $data
     *
     * @return string[]
     */
    private function extractHeaders(iterable $data): array
    {
        $headers = [];
        foreach ($data as $row) {
            $headers += array_flip(array_keys($row));
        }

        return array_keys($headers);
    }

    /**
     * @return mixed[]
     * @phpstan-return array{0: string, 1: string, 2: bool, 3: string, 4: string, 5: bool, 6: bool}
     */
    private function getOptions(): array
    {
        if ($this->rootMetadata === null) {
            return [',', '.', false, '"', '\\', false, false];
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
