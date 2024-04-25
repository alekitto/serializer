<?php

declare(strict_types=1);

namespace Kcs\Serializer\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Csv
{
    public function __construct(
        public string|null $delimiter = null,
        public string|null $enclosure = null,
        public string|null $escapeChar = null,
        public bool|null $escapeFormulas = null,
        public string|null $keySeparator = null,
        public bool|null $printHeaders = null,
        public bool|null $outputBom = null,
    ) {
    }
}
