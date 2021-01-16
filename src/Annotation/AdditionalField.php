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
final class AdditionalField
{
    /**
     * @Required
     */
    public string $name;

    /**
     * @var array<mixed>
     */
    public array $attributes = [];

    public function __construct($name, ?array $attributes = null)
    {
        if (is_string($name)) {
            $data = ['name' => $name];
        } elseif (is_array($name)) {
            $data = $name;
        } else {
            throw new TypeError(sprintf('Argument #1 passed to %s must be a string. %s passed', __METHOD__, get_debug_type($name)));
        }

        $this->name = $data['name'] ?? $data['value'];
        $this->attributes = array_map(static function ($element): object {
            if (is_object($element)) {
                return $element;
            }

            if (is_string($element)) {
                return new $element();
            }

            [$className, $args] = $element + [null, []];

            return new $className(...$args);
        }, $attributes ?? $data['attributes'] ?? []);
    }
}
