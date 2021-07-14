<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use TypeError;

use function get_debug_type;
use function is_array;
use function is_string;
use function Safe\sprintf;

abstract class Version
{
    /** @Required */
    public string $version;

    public function __construct($version)
    {
        if (is_string($version)) {
            $data = ['version' => $version];
        } elseif (is_array($version)) {
            $data = $version;
        } else {
            throw new TypeError(sprintf('Argument #1 passed to %s must be a string. %s passed', __METHOD__, get_debug_type($version)));
        }

        $this->version = $data['version'] ?? $data['value'];
    }
}
