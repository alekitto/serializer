<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;
use TypeError;

use function array_intersect_key;
use function get_debug_type;
use function is_array;
use function sprintf;

/**
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Discriminator
{
    /**
     * @Required
     * @var array<string, class-string>
     */
    public array $map;

    public string $field = 'type';

    public bool $disabled = false;

    /** @var string|string[]|null */
    public string|array|null $groups;

    /**
     * @param array<string, mixed> $map
     * @phpstan-param array{map?: array<string, mixed>, value?: array<string, mixed>, field?: string, disabled?: string, groups?: string|string[]}|array<string, mixed> $map
     */
    public function __construct(array $map, string|null $field = null, bool|null $disabled = null, array|null $groups = null)
    {
        if (! is_array($map)) {
            throw new TypeError(sprintf('Argument #1 passed to %s must be an array. %s passed', __METHOD__, get_debug_type($map)));
        }

        if (! empty(array_intersect_key($map, ['value' => true, 'map' => true, 'field' => true, 'disabled' => true, 'groups' => true]))) {
            $data = $map;
        } else {
            $data = ['map' => $map];
        }

        $this->map = $data['map'] ?? $data['value'];
        $this->field = $field ?? $data['field'] ?? 'type';
        $this->disabled = $disabled ?? $data['disabled'] ?? false;
        $this->groups = $groups ?? $data['groups'] ?? null;
    }
}
