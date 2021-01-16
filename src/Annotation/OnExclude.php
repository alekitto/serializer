<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;
use Kcs\Serializer\Exception\RuntimeException;
use TypeError;

use function Safe\sprintf;

/**
 * @Annotation
 * @Target({"PROPERTY", "ANNOTATION"})
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class OnExclude
{
    public const NULL = 'null';
    public const SKIP = 'skip';

    /**
     * @var string
     */
    public string $policy = self::NULL;

    public function __construct($policy)
    {
        if (is_string($policy)) {
            $data = ['policy' => $policy];
        } elseif (is_array($policy)) {
            $data = $policy;
        } else {
            throw new TypeError(sprintf('Argument #1 passed to %s must be a string. %s passed', __METHOD__, get_debug_type($policy)));
        }

        $policy = $data['policy'] ?? $data['value'];
        $this->policy = \strtolower($policy);

        if (self::NULL !== $this->policy && self::SKIP !== $this->policy) {
            throw new RuntimeException('OnExclude policy must either be "null", or "skip".');
        }
    }
}
