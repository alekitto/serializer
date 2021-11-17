<?php

declare(strict_types=1);

namespace Kcs\Serializer\Construction;

use Kcs\Serializer\DeserializationContext;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;

/**
 * Object constructor that allows deserialization into already constructed
 * objects passed through the deserialization context.
 */
class InitializedObjectConstructor implements ObjectConstructorInterface
{
    public function __construct(private ObjectConstructorInterface $fallbackConstructor)
    {
    }

    public function construct(VisitorInterface $visitor, ClassMetadata $metadata, mixed $data, Type $type, DeserializationContext $context): object
    {
        if ($context->getDepth() === 1 && $context->attributes->has('target')) {
            return $context->attributes->get('target');
        }

        return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
    }
}
