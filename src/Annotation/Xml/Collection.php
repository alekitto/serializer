<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation\Xml;

use TypeError;

use function Safe\sprintf;

abstract class Collection
{
    public string $entry = 'entry';
    public bool $inline = false;
    public ?string $namespace = null;

    public function __construct($entry = 'entry', bool $inline = null, ?string $namespace = null)
    {
        if (is_string($entry)) {
            $data = ['entry' => $entry];
        } elseif (is_array($entry)) {
            $data = $entry;
        } elseif (null !== $entry) {
            throw new TypeError(sprintf('Argument #1 passed to %s must be a string or null. %s passed', __METHOD__, get_debug_type($entry)));
        }

        $this->entry = $data['entry'] ?? $data['value'] ?? 'entry';
        $this->inline = $inline ?? $data['inline'] ?? false;
        $this->namespace = $namespace ?? $data['namespace'] ?? null;
    }
}
