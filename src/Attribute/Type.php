<?php

declare(strict_types=1);

namespace Kcs\Serializer\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class Type
{
    public function __construct(public string $name)
    {
    }
}
