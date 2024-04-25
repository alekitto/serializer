<?php

declare(strict_types=1);

namespace Kcs\Serializer\Attribute;

use Attribute;

use function array_map;
use function is_object;
use function is_string;

#[Attribute(Attribute::TARGET_CLASS)]
final class AdditionalField
{
    /** @var array<mixed> */
    public array $attributes = [];

    /** @param array<mixed> $attributes */
    public function __construct(public string $name, array $attributes = [])
    {
        $this->attributes = array_map(static function ($element): object {
            if (is_object($element)) {
                return $element;
            }

            if (is_string($element)) {
                return new $element();
            }

            [$className, $args] = $element + [null, []];

            return new $className(...$args);
        }, $attributes);
    }
}
