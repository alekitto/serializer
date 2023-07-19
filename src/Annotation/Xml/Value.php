<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation\Xml;

use Attribute;

use function is_array;
use function is_bool;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
final class Value
{
    public bool $cdata = true;

    /**
     * @param array<string, mixed>|bool|null $cdata
     * @phpstan-param array{cdata?: bool, value?: bool}|bool|null $cdata
     */
    public function __construct(array|bool|null $cdata = null)
    {
        if (is_bool($cdata)) {
            $data = ['cdata' => $cdata];
        } elseif (is_array($cdata)) {
            $data = $cdata;
        }

        $this->cdata = $data['cdata'] ?? $data['value'] ?? true;
    }
}
