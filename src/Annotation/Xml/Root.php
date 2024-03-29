<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation\Xml;

use Attribute;
use TypeError;

use function get_debug_type;
use function is_array;
use function is_string;
use function sprintf;

/**
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Root
{
    /** @Required */
    public string $name;
    public string|null $namespace = null;
    public string|null $encoding = null;

    /**
     * @param array<string, mixed>|string $name
     * @phpstan-param array{name?: string, value?: string, namespace?: string, encoding?: string}|string $name
     */
    public function __construct(array|string $name, string|null $namespace = null, string|null $encoding = null)
    {
        if (is_string($name)) {
            $data = ['name' => $name];
        } elseif (is_array($name)) {
            $data = $name;
        } else {
            throw new TypeError(sprintf('Argument #1 passed to %s must be a string. %s passed', __METHOD__, get_debug_type($name)));
        }

        $this->name = $data['name'] ?? $data['value'];
        $this->namespace = $namespace ?? $data['namespace'] ?? null;
        $this->encoding = $encoding ?? $data['encoding'] ?? null;
    }
}
