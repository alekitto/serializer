<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;
use TypeError;

use function Safe\sprintf;

/**
 * @Annotation
 * @Target({"CLASS","PROPERTY"})
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final class ReadOnly
{
    public bool $readOnly = true;

    /**
     * @param array<string, mixed>|bool $readOnly
     */
    public function __construct($readOnly = true)
    {
        if (is_bool($readOnly)) {
            $data = ['readOnly' => $readOnly];
        } elseif (is_array($readOnly)) {
            $data = $readOnly;
        } elseif (null !== $readOnly) {
            throw new TypeError(sprintf('Argument #1 passed to %s must be a bool. %s passed', __METHOD__, get_debug_type($readOnly)));
        }

        $this->readOnly = $data['readOnly'] ?? $data['value'] ?? true;
    }
}
