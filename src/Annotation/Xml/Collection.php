<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation\Xml;

abstract class Collection
{
    public function __construct(
        public string $entry = 'entry',
        public bool $inline = false,
        public string|null $namespace = null,
    ) {
    }
}
