<?php declare(strict_types=1);

namespace Kcs\Serializer\Exclusion;

use Kcs\Serializer\SerializationContext;

interface SerializationGroupProviderInterface
{
    /**
     * Returns the serialization groups for the current object and its
     * children. The current serialization context could be used to
     * inherit the parent groups.
     */
    public function getSerializationGroups(SerializationContext $context): iterable;
}
