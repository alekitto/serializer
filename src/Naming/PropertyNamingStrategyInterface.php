<?php declare(strict_types=1);

namespace Kcs\Serializer\Naming;

use Kcs\Serializer\Metadata\PropertyMetadata;

/**
 * Interface for property naming strategies.
 *
 * Implementations translate the property name to a serialized name that is
 * displayed.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface PropertyNamingStrategyInterface
{
    /**
     * Translates the name of the property to the serialized version.
     *
     * @param PropertyMetadata $property
     *
     * @return string
     */
    public function translateName(PropertyMetadata $property): string;
}
