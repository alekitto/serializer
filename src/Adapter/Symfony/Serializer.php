<?php declare(strict_types=1);

namespace Kcs\Serializer\Adapter\Symfony;

use Kcs\Serializer\Direction;
use Kcs\Serializer\SerializerInterface;
use Kcs\Serializer\Type\Type;
use Symfony\Component\Serializer\Encoder\ContextAwareDecoderInterface;
use Symfony\Component\Serializer\Encoder\ContextAwareEncoderInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;

final class Serializer implements SymfonySerializerInterface, ContextAwareNormalizerInterface, ContextAwareDenormalizerInterface, ContextAwareEncoderInterface, ContextAwareDecoderInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($data, $format, array $context = [])
    {
        return $this->serializer->serialize($data, $format, ContextConverter::toSerializationContext($context));
    }

    /**
     * {@inheritdoc}
     */
    public function deserialize($data, $type, $format, array $context = [])
    {
        return $this->serializer->deserialize($data, Type::parse($type), $format, ContextConverter::toDeserializationContext($context));
    }

    /**
     * @inheritDoc
     */
    public function supportsDecoding($format, array $context = [])
    {
        if (! \method_exists($this->serializer, 'supports')) {
            return true;
        }

        return $this->serializer->supports($format, Direction::DIRECTION_DESERIALIZATION);
    }

    /**
     * @inheritDoc
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function supportsEncoding($format, array $context = [])
    {
        if (! \method_exists($this->serializer, 'supports')) {
            return true;
        }

        return $this->serializer->supports($format, Direction::DIRECTION_SERIALIZATION);
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function decode($data, $format, array $context = [])
    {
        return $this->serializer->deserialize($data, Type::parse('array'), $format, ContextConverter::toDeserializationContext($context));
    }

    /**
     * @inheritDoc
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        return $this->serializer->deserialize($data, Type::parse($type), $format ?? 'array', ContextConverter::toDeserializationContext($context));
    }

    /**
     * @inheritDoc
     */
    public function encode($data, $format, array $context = [])
    {
        return $this->serializer->serialize($data, $format, ContextConverter::toSerializationContext($context));
    }

    /**
     * @inheritDoc
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return $this->serializer->serialize($object, $format ?? 'array', ContextConverter::toSerializationContext($context));
    }
}
