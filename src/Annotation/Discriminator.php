<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;

use function explode;
use function is_string;

#[Attribute(Attribute::TARGET_CLASS)]
final class Discriminator
{
    public array|null $groups = null;

    /**
     * @param array<string, class-string> $map
     * @param string|string[]|null $groups
     */
    public function __construct(
        public array $map,
        public string $field = 'type',
        public bool $disabled = false,
        array|string|null $groups = null,
    ) {
        if (is_string($groups)) {
            $this->groups = explode(',', $groups);
        } else {
            $this->groups = $groups;
        }
    }
}
