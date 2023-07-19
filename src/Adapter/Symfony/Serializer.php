<?php

declare(strict_types=1);

namespace Kcs\Serializer\Adapter\Symfony;

use Kcs\Serializer\SerializerInterface;
use Kcs\Serializer\Type\Type;
use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;

final class Serializer implements SymfonySerializerInterface
{
    public function __construct(private SerializerInterface $serializer)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function serialize(mixed $data, string $format, array $context = []): string
    {
        return $this->serializer->serialize($data, $format, ContextConverter::toSerializationContext($context));
    }

    /**
     * {@inheritdoc}
     *
     * @phpstan-param string $type
     * @phpstan-param string $format
     */
    public function deserialize(mixed $data, string $type, string $format, array $context = []): mixed
    {
        return $this->serializer->deserialize($data, Type::parse($type), $format, ContextConverter::toDeserializationContext($context));
    }
}
