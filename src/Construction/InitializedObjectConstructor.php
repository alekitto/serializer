<?php declare(strict_types=1);

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
    private $fallbackConstructor;

    /**
     * Constructor.
     *
     * @param ObjectConstructorInterface $fallbackConstructor Fallback object constructor
     */
    public function __construct(ObjectConstructorInterface $fallbackConstructor)
    {
        $this->fallbackConstructor = $fallbackConstructor;
    }

    /**
     * {@inheritdoc}
     */
    public function construct(VisitorInterface $visitor, ClassMetadata $metadata, $data, Type $type, DeserializationContext $context)
    {
        if (1 === $context->getDepth() && $context->attributes->has('target')) {
            return $context->attributes->get('target');
        }

        return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
    }
}
