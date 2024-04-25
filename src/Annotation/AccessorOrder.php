<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;
use Kcs\Serializer\Metadata\Access\Order;

/**
 * Controls the order of properties in a class.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class AccessorOrder
{
    public function __construct(
        public Order $order,
        public string|array $custom = [],
    ) {
    }
}
