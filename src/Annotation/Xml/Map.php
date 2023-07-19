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

    /**
     * @param array<string, mixed>|string $entry
     * @phpstan-param array{entry?: string, value?: string, inline?: bool, namespace?: string, keyAttribute?: string}|string $entry
     */
    public function __construct(array|string $entry = 'entry', bool|null $inline = null, string|null $namespace = null, string|null $keyAttribute = null)
    {
        $data = [];
        if (is_array($entry)) {
            $data = $entry;
        }

        parent::__construct($entry, $inline, $namespace);

        $this->keyAttribute = $keyAttribute ?? $data['keyAttribute'] ?? '_key';
    }
}
