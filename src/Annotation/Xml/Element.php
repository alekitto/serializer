<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation\Xml;

use Attribute;
use TypeError;

use function get_debug_type;
use function is_array;
use function is_bool;
use function Safe\sprintf;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
final class Element
{
    public bool $cdata = true;
    public ?string $namespace = null;

    /**
     * @param array<string, mixed>|bool $cdata
     * @phpstan-param array{cdata?: bool, value?: bool, namespace?: string}|bool $cdata
     */
    public function __construct($cdata = true, ?string $namespace = null)
    {
        if (is_bool($cdata)) {
            $data = ['cdata' => $cdata];
        } elseif (is_array($cdata)) {
            $data = $cdata;
        } elseif ($cdata !== null) {
            throw new TypeError(sprintf('Argument #1 passed to %s must be a bool. %s passed', __METHOD__, get_debug_type($namespace)));
        }

        $this->cdata = $data['cdata'] ?? $data['value'] ?? true;
        $this->namespace = $namespace ?? $data['namespace'] ?? null;
    }
}
