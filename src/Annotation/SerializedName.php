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
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class SerializedName
{
    public string $name;

    /**
     * @param array<string, mixed>|string $name
     * @phpstan-param array{name?: string, value?: string}|string $name
     */
    public function __construct(array|string $name)
    {
        if (is_string($name)) {
            $data = ['name' => $name];
        } elseif (is_array($name)) {
            $data = $name;
        } else {
            throw new TypeError(sprintf('Argument #1 passed to %s must be a string. %s passed', __METHOD__, get_debug_type($name)));
        }

        $this->name = $data['name'] ?? $data['value'];
    }
}
