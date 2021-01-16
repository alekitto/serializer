<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation\Xml;

use Attribute;
use TypeError;

use function Safe\sprintf;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
final class Value
{
    public bool $cdata = true;

    public function __construct($cdata = null)
    {
        if (is_bool($cdata)) {
            $data = ['cdata' => $cdata];
        } elseif (is_array($cdata)) {
            $data = $cdata;
        } elseif (null !== $cdata) {
            throw new TypeError(sprintf('Argument #1 passed to %s must be a bool or null. %s passed', __METHOD__, get_debug_type($cdata)));
        }

        $this->cdata = $data['cdata'] ?? $data['value'] ?? true;
    }
}
