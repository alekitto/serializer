<?php

declare(strict_types=1);

namespace Kcs\Serializer\Attribute\Xml;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Root
{
    public function __construct(
        public string $name,
        public string|null $namespace = null,
        public string|null $encoding = null,
    ) {
    }
}
