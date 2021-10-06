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

    /**
     * @param array<string, mixed>|string|null $getter
     * @phpstan-param array{getter?: string, value?: string, setter?: string}|string|null $getter
     */
    public function __construct(array | string | null $getter = null, ?string $setter = null)
    {
        if (is_string($getter)) {
            $data = ['getter' => $getter];
        } elseif (is_array($getter)) {
            $data = $getter;
        }

        $this->getter = $data['getter'] ?? $data['value'] ?? null;
        $this->setter = $setter ?? $data['setter'] ?? null;
    }
}
