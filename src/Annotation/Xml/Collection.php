<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation\Xml;

use TypeError;

use function get_debug_type;
use function is_array;
use function is_string;
use function Safe\sprintf;

abstract class Collection
{
    public string $entry = 'entry';
    public bool $inline = false;
    public ?string $namespace = null;

    /**
     * @param array<string, mixed>|string $entry
     * @phpstan-param array{entry?: string, value?: string, inline?: bool, namespace?: string}|string $entry
     */
    public function __construct(array|string $entry = 'entry', ?bool $inline = null, ?string $namespace = null)
    {
        if (is_string($entry)) {
            $data = ['entry' => $entry];
        } elseif (is_array($entry)) {
            $data = $entry;
        } elseif ($entry !== null) {
            throw new TypeError(sprintf('Argument #1 passed to %s must be a string or null. %s passed', __METHOD__, get_debug_type($entry)));
        }

        $this->entry = $data['entry'] ?? $data['value'] ?? 'entry';
        $this->inline = $inline ?? $data['inline'] ?? false;
        $this->namespace = $namespace ?? $data['namespace'] ?? null;
    }
}
