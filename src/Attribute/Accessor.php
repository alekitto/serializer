<?php

declare(strict_types=1);

namespace Kcs\Serializer\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Accessor
{
    public function __construct(
        public string|null $getter = null,
        public string|null $setter = null,
    ) {
    }
}
