<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;
use TypeError;

use function get_debug_type;
use function is_array;
use function is_int;
use function Safe\sprintf;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
final class MaxDepth
{
    /** @Required */
    public int $depth;

    /**
     * @param array<string, mixed>|int $depth
     * @phpstan-param array{depth?: int, value?: int}|int $depth
     */
    public function __construct($depth)
    {
        if (is_int($depth)) {
            $data = ['depth' => $depth];
        } elseif (is_array($depth)) {
            $data = $depth;
        } else {
            throw new TypeError(sprintf('Argument #1 passed to %s must be an integer. %s passed', __METHOD__, get_debug_type($depth)));
        }

        $this->depth = $data['depth'] ?? $data['value'];
    }
}
