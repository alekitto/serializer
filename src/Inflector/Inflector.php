<?php declare(strict_types=1);

namespace Kcs\Serializer\Inflector;

use Doctrine\Inflector\Inflector as DoctrineInflector;
use Doctrine\Inflector\InflectorFactory;

if (! class_exists(\Doctrine\Inflector\InflectorFactory::class)) {
    class Inflector
    {
        private static ?self $instance = null;

        /**
         * @internal
         */
        public function __construct()
        {
        }

        public function classify(string $word): string
        {
            return \Doctrine\Common\Inflector\Inflector::classify($word);
        }

        public function camelize(string $word): string
        {
            return \Doctrine\Common\Inflector\Inflector::camelize($word);
        }

        public static function getInstance(): self
        {
            if (null === self::$instance) {
                self::$instance = new self();
            }

            return self::$instance;
        }
    }
} else {
    class Inflector
    {
        private static ?self $instance = null;
        private DoctrineInflector $inflector;

        /**
         * @internal
         */
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
}
