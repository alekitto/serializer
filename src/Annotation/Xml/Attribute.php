<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation\Xml;

use Attribute as PhpAttribute;
use TypeError;

use function get_debug_type;
use function is_array;
use function is_string;
use function Safe\sprintf;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[PhpAttribute(PhpAttribute::TARGET_METHOD | PhpAttribute::TARGET_PROPERTY)]
final class Attribute
{
    public ?string $namespace = null;

    /**
     * @param array<string, mixed>|string|null $namespace
     * @phpstan-param array{namespace?: string, value?: string}|string|null $namespace
     */
    public function __construct(array | string | null $namespace = null)
    {
        if (is_string($namespace)) {
            $data = ['namespace' => $namespace];
        } elseif (is_array($namespace)) {
            $data = $namespace;
        }

        $this->namespace = $data['namespace'] ?? $data['value'] ?? null;
    }
}
