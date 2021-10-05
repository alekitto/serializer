<?php

declare(strict_types=1);

namespace Kcs\Serializer\Construction;

use Kcs\Serializer\DeserializationContext;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;

/**
 * Implementations of this interface construct new objects during deserialization.
 */
interface ObjectConstructorInterface
{
    /**
     * Constructs a new object.
     *
     * Implementations could for example create a new object calling "new", use
     * "unserialize" techniques, reflection, or other means.
     */
    public function construct(VisitorInterface $visitor, ClassMetadata $metadata, mixed $data, Type $type, DeserializationContext $context): object;
}
