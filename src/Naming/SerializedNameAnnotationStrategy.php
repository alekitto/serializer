<?php declare(strict_types=1);

namespace Kcs\Serializer\Naming;

use Kcs\Serializer\Metadata\PropertyMetadata;

/**
 * Naming strategy which uses an annotation to translate the property name.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class SerializedNameAnnotationStrategy implements PropertyNamingStrategyInterface
{
    /**
     * @var PropertyNamingStrategyInterface
     */
    private $delegate;

    public function __construct(PropertyNamingStrategyInterface $namingStrategy)
    {
        $this->delegate = $namingStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function translateName(PropertyMetadata $property): string
    {
        if (null !== $name = $property->serializedName) {
            return $name;
        }

        return $this->delegate->translateName($property);
    }
}
