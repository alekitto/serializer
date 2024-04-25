<?php

declare(strict_types=1);

namespace Kcs\Serializer\Attribute\Xml;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
final class Element
{
    public function __construct(public bool $cdata = true, public string|null $namespace = null)
    {
    }
}
