<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;
use TypeError;

use function get_debug_type;
use function is_array;
use function is_bool;
use function Safe\sprintf;

/**
 * @Annotation
 * @Target({"CLASS","PROPERTY"})
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final class Immutable
{
    public bool $immutable = true;

    /**
     * @param array<string, mixed>|bool $immutable
     */
    public function __construct($immutable = true)
    {
        if (is_bool($immutable)) {
            $data = ['immutable' => $immutable];
        } elseif (is_array($immutable)) {
            $data = $immutable;
        } elseif ($immutable !== null) {
            throw new TypeError(sprintf('Argument #1 passed to %s must be a bool. %s passed', __METHOD__, get_debug_type($immutable)));
        }

        $this->immutable = $data['immutable'] ?? $data['value'] ?? true;
    }
}
