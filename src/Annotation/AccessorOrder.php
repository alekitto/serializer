<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;
use Kcs\Serializer\Metadata\ClassMetadata;
use TypeError;

use function get_debug_type;
use function is_array;
use function is_string;
use function Safe\sprintf;

/**
 * Controls the order of properties in a class.
 *
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class AccessorOrder
{
    public const UNDEFINED = ClassMetadata::ACCESSOR_ORDER_UNDEFINED;
    public const ALPHABETICAL = ClassMetadata::ACCESSOR_ORDER_ALPHABETICAL;
    public const CUSTOM = ClassMetadata::ACCESSOR_ORDER_CUSTOM;

    /** @Required */
    public string $order;

    /** @var array<string> */
    public array $custom = [];

    public function __construct($order, ?array $custom = null)
    {
        if (is_string($order)) {
            $data = ['order' => $order];
        } elseif (is_array($order)) {
            $data = $order;
        } else {
            throw new TypeError(sprintf('Argument #1 passed to %s must be a string. %s passed', __METHOD__, get_debug_type($order)));
        }

        $this->order = $data['order'] ?? $data['value'];
        $this->custom = $custom ?? $data['custom'] ?? [];
    }
}
