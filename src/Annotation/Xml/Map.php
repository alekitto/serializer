<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation\Xml;

use Attribute;

use function is_array;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
final class Map extends Collection
{
    public string $keyAttribute = '_key';

    public function __construct($entry = 'entry', ?bool $inline = null, ?string $namespace = null, ?string $keyAttribute = null)
    {
        $data = [];
        if (is_array($entry)) {
            $data = $entry;
        }

        parent::__construct($entry, $inline, $namespace);

        $this->keyAttribute = $keyAttribute ?? $data['keyAttribute'] ?? '_key';
    }
}
