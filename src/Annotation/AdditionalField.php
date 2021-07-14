<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;
use TypeError;

use function array_map;
use function get_debug_type;
use function is_array;
use function is_object;
use function is_string;
use function Safe\sprintf;

/**
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class AdditionalField
{
    /** @Required */
    public string $name;

    /** @var array<mixed> */
    public array $attributes = [];

    /**
     * @param array<string, mixed>|string $name
     * @phpstan-param array{name?: string, value?: string, attributes?: object[]|array{string, string}[]}|string $name
     */
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
