<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation\Xml;

use Attribute as PhpAttribute;
use TypeError;
use function Safe\sprintf;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[PhpAttribute(PhpAttribute::TARGET_METHOD | PhpAttribute::TARGET_PROPERTY)]
final class Attribute
{
    public ?string $namespace = null;

    public function __construct($namespace = null)
    {
        if (is_string($namespace)) {
            $data = ['namespace' => $namespace];
        } elseif (is_array($namespace)) {
            $data = $namespace;
        } elseif (null !== $namespace) {
            throw new TypeError(sprintf('Argument #1 passed to %s must be a string or null. %s passed', __METHOD__, get_debug_type($namespace)));
        }

        $this->namespace = $data['namespace'] ?? $data['value'] ?? null;
    }
}
