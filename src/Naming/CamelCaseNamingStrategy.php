<?php declare(strict_types=1);

namespace Kcs\Serializer\Naming;

use Kcs\Serializer\Metadata\PropertyMetadata;

/**
 * Generic naming strategy which translates a camel-cased property name.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 * @deprecated
 */
final class CamelCaseNamingStrategy implements PropertyNamingStrategyInterface
{
    /**
     * @var string
     */
    private $separator;

    /**
     * @var bool
     */
    private $lowerCase;

    public function __construct(string $separator = '_', bool $lowerCase = true)
    {
        $this->separator = $separator;
        $this->lowerCase = $lowerCase;
    }

    /**
     * {@inheritdoc}
     */
    public function translateName(PropertyMetadata $property): string
    {
        @\trigger_error('CamelCaseNamingStrategy is deprecated. Use UnderscoreNamingStrategy, IdenticalPropertyNamingStrategy or implement your own strategy.', E_USER_DEPRECATED);
        $name = \preg_replace('/[A-Z]/', $this->separator.'\\0', $property->name);

        if ($this->lowerCase) {
            return \strtolower($name);
        }

        return \ucfirst($name);
    }
}
