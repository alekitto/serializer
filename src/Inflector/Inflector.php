<?php declare(strict_types=1);

namespace Kcs\Serializer\Inflector;

use Doctrine\Inflector\Inflector as DoctrineInflector;
use Doctrine\Inflector\InflectorFactory;

class Inflector
{
    private static ?self $instance = null;
    private DoctrineInflector $inflector;

    public function __construct()
    {
        $this->inflector = InflectorFactory::create()->build();
    }

    public function classify(string $word): string
    {
        return $this->inflector->classify($word);
    }

    public function camelize(string $word): string
    {
        return $this->inflector->camelize($word);
    }

    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
