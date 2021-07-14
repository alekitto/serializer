<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;
use TypeError;

use function get_debug_type;
use function is_array;
use function is_string;
use function Safe\sprintf;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Accessor
{
    public ?string $getter = null;
    public ?string $setter = null;

    public function __construct($getter = null, ?string $setter = null)
    {
        if (is_string($getter)) {
            $data = ['getter' => $getter];
        } elseif (is_array($getter)) {
            $data = $getter;
        } elseif ($getter !== null) {
            throw new TypeError(sprintf('Argument #1 passed to %s must be a string. %s passed', __METHOD__, get_debug_type($getter)));
        }

        $this->getter = $data['getter'] ?? $data['value'] ?? null;
        $this->setter = $setter ?? $data['setter'] ?? null;
    }
}
