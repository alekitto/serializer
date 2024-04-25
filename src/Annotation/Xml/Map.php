<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation\Xml;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
final class Map extends Collection
{
    public function __construct(string $entry = 'entry', bool $inline = false, string|null $namespace = null, public string $keyAttribute = '_key')
    {
        parent::__construct($entry, $inline, $namespace);
    }
}
