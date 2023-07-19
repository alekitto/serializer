<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;
use TypeError;

use function array_intersect_key;
use function get_debug_type;
use function is_array;
use function is_string;
use function sprintf;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class Groups
{
    /**
     * @Required
     * @var array<int | string, mixed>
     */
    public array $groups;

    /**
     * @param array<string, mixed>|string[]|string $groups
     * @phpstan-param array{groups?: string[]|array<string, mixed>, value?: string[]|array<string, mixed>}|array<string, mixed>|string[]|string $groups
     */
    public function __construct(array|string $groups)
    {
        if (is_string($groups)) {
            $data = ['groups' => [$groups]];
        } elseif (is_array($groups)) {
            if (! empty(array_intersect_key($groups, ['value' => true, 'groups' => true]))) {
                $data = $groups;
            } else {
                $data = ['groups' => $groups];
            }
        } else {
            throw new TypeError(sprintf('Argument #1 passed to %s must be an array or string. %s passed', __METHOD__, get_debug_type($groups)));
        }

        $groups = $data['groups'] ?? $data['value'];
        $this->groups = (array) $groups;
    }
}
