<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;
use TypeError;

use function Safe\sprintf;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[Attribute(Attribute::TARGET_PROPERTY, Attribute::TARGET_METHOD)]
final class Groups
{
    /**
     * @Required
     *
     * @var array<string>
     */
    public array $groups;

    public function __construct($groups)
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
