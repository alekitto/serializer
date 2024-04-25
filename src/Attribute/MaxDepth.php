<?php

declare(strict_types=1);

namespace Kcs\Serializer\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
final class MaxDepth
{
    public function __construct(public int $depth)
    {
    }
}
