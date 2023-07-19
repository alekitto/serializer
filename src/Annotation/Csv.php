<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;

use function is_array;
use function is_string;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Csv
{
    public string|null $delimiter;
    public string|null $enclosure;
    public string|null $escapeChar;
    public bool|null $escapeFormulas;
    public string|null $keySeparator;
    public bool|null $printHeaders;
    public bool|null $outputBom;

    /**
     * @param array<string, mixed>|string $delimiter
     * @phpstan-param array{delimiter?: string, value?: string, enclosure?: string, escapeChar?: string, escapeFormulas?: bool, keySeparator?: string, printHeaders?: bool, outputBom?: bool}|string $delimiter
     */
    public function __construct(array|string|null $delimiter = null, string|null $enclosure = null, string|null $escapeChar = null, bool|null $escapeFormulas = null, string|null $keySeparator = null, bool|null $printHeaders = null, bool|null $outputBom = null)
    {
        if (is_string($delimiter)) {
            $data = ['delimiter' => $delimiter];
        } elseif (is_array($delimiter)) {
            $data = $delimiter;
        }

        $this->delimiter = $data['delimiter'] ?? $data['value'] ?? null;
        $this->enclosure = $enclosure ?? $data['enclosure'] ?? null;
        $this->escapeChar = $escapeChar ?? $data['escapeChar'] ?? null;
        $this->escapeFormulas = $escapeFormulas ?? $data['escapeFormulas'] ?? null;
        $this->keySeparator = $keySeparator ?? $data['keySeparator'] ?? null;
        $this->printHeaders = $printHeaders ?? $data['printHeaders'] ?? null;
        $this->outputBom = $outputBom ?? $data['outputBom'] ?? null;
    }
}
