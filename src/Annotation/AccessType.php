<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;
use Kcs\Serializer\Metadata\PropertyMetadata;
use TypeError;

use function get_debug_type;
use function is_array;
use function is_string;
use function Safe\sprintf;

/**
 * @Annotation
 * @Target({"CLASS", "PROPERTY"})
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final class AccessType
{
    public const PROPERTY = PropertyMetadata::ACCESS_TYPE_PROPERTY;
    public const PUBLIC_METHOD = PropertyMetadata::ACCESS_TYPE_PUBLIC_METHOD;

    /** @Required */
    public string $type;

    /**
     * @param array<string, mixed>|string $type
     * @phpstan-param array{type?: string, value?: string}|string $type
     */
    public function __construct($type)
    {
        if (is_string($type)) {
            $data = ['type' => $type];
        } elseif (is_array($type)) {
            $data = $type;
        } else {
            throw new TypeError(sprintf('Argument #1 passed to %s must be a string. %s passed', __METHOD__, get_debug_type($type)));
        }

        $this->type = $data['type'] ?? $data['value'];
    }
}
