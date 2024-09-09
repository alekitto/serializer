<?php

declare(strict_types=1);

namespace Kcs\Serializer\Inflector;

use function lcfirst;
use function str_replace;
use function ucwords;

class Inflector
{
    private static self|null $instance = null;

    public function classify(string $word): string
    {
        return str_replace([' ', '_', '-'], '', ucwords($word, ' _-'));
    }

    public function camelize(string $word): string
    {
        return lcfirst($this->classify($word));
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
