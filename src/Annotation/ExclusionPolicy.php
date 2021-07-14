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
use function strtoupper;

/**
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class ExclusionPolicy
{
    public const NONE = 'NONE';
    public const ALL = 'ALL';

    /** @Required */
    public string $policy;

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
        $this->policy = strtoupper($policy);

        if ($this->policy !== self::NONE && $this->policy !== self::ALL) {
            throw new RuntimeException('Exclusion policy must either be "ALL", or "NONE".');
        }
    }
}
