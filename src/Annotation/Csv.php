<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;
use TypeError;

use function Safe\sprintf;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Csv
{
    public ?string $delimiter;
    public ?string $enclosure;
    public ?string $escapeChar;
    public ?bool $escapeFormulas;
    public ?string $keySeparator;
    public ?bool $printHeaders;
    public ?bool $outputBom;

    public function __construct($delimiter, ?string $enclosure = null, ?string $escapeChar = null, ?bool $escapeFormulas = null, ?string $keySeparator = null, ?bool $printHeaders = null, ?bool $outputBom = null)
    {
        if (is_string($delimiter)) {
            $data = ['delimiter' => $delimiter];
        } elseif (is_array($delimiter)) {
            $data = $delimiter;
        } elseif (null !== $delimiter) {
            throw new TypeError(sprintf('Argument #1 passed to %s must be a string or null. %s passed', __METHOD__, get_debug_type($delimiter)));
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
