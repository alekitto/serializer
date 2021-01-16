<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;
use TypeError;

use function Safe\sprintf;

/**
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Discriminator
{
    /**
     * @Required
     *
     * @var array<string>
     */
    public array $map;

    /**
     * @var string
     */
    public string $field = 'type';

    /**
     * @var bool
     */
    public bool $disabled = false;

    /**
     * @var array<string>
     */
    public ?array $groups = null;

    public function __construct($map, ?string $field = null, ?bool $disabled = null, ?array $groups = null)
    {
        if (is_array($map)) {
            if (! empty(array_intersect_key($map, ['value' => true, 'map' => true, 'field' => true, 'disabled' => true, 'groups' => true]))) {
                $data = $map;
            } else {
                $data = ['map' => $map];
            }
        } else {
            throw new TypeError(sprintf('Argument #1 passed to %s must be an array. %s passed', __METHOD__, get_debug_type($map)));
        }

        $this->map = $data['map'] ?? $data['value'];
        $this->field = $field ?? $data['field'] ?? 'type';
        $this->disabled = $disabled ?? $data['disabled'] ?? false;
        $this->groups = $groups ?? $data['groups'] ?? null;
    }
}
