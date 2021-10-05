<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;
use Kcs\Serializer\Exception\RuntimeException;
use TypeError;

use function get_debug_type;
use function is_array;
use function is_string;
use function Safe\sprintf;
use function strtolower;

/**
 * @Annotation
 * @Target({"PROPERTY", "ANNOTATION"})
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class OnExclude
{
    public const NULL = 'null';
    public const SKIP = 'skip';

    public string $policy = self::NULL;

    /**
     * @param array<string, mixed>|string $policy
     * @phpstan-param array{policy?: string, value?: string}|string $policy
     */
    public function __construct(array|string $policy)
    {
        if (is_string($policy)) {
            $data = ['policy' => $policy];
        } elseif (is_array($policy)) {
            $data = $policy;
        } else {
            throw new TypeError(sprintf('Argument #1 passed to %s must be a string. %s passed', __METHOD__, get_debug_type($policy)));
        }

        $policy = $data['policy'] ?? $data['value'];
        $this->policy = strtolower($policy);

        if ($this->policy !== self::NULL && $this->policy !== self::SKIP) {
            throw new RuntimeException('OnExclude policy must either be "null", or "skip".');
        }
    }
}
