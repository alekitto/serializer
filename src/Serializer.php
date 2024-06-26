<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Metadata\Factory\MetadataFactoryInterface;
use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Exception\UnsupportedFormatException;
use Kcs\Serializer\Handler\HandlerRegistryInterface;
use Kcs\Serializer\Naming\PropertyNamingStrategyInterface;
use Kcs\Serializer\Naming\SerializedNameAttributeStrategy;
use Kcs\Serializer\Naming\UnderscoreNamingStrategy;
use Kcs\Serializer\Type\Type;
use Psr\EventDispatcher\EventDispatcherInterface;

use function get_debug_type;
use function is_array;
use function sprintf;

class Serializer implements SerializerInterface
{
    private GraphNavigator $navigator;
    private readonly PropertyNamingStrategyInterface $defaultNamingStrategy;

    /**
     * @param VisitorInterface[] $serializationVisitors
     * @param VisitorInterface[] $deserializationVisitors
     */
    public function __construct(
        private readonly MetadataFactoryInterface $factory,
        private readonly HandlerRegistryInterface $handlerRegistry,
        private readonly ObjectConstructorInterface $objectConstructor,
        private readonly array $serializationVisitors,
        private readonly array $deserializationVisitors,
        private readonly EventDispatcherInterface|null $dispatcher = null,
        PropertyNamingStrategyInterface|null $defaultNamingStrategy = null,
    ) {
        $this->defaultNamingStrategy = $defaultNamingStrategy ?? new SerializedNameAttributeStrategy(new UnderscoreNamingStrategy());
    }

    public function serialize(mixed $data, string $format, SerializationContext|null $context = null, Type|null $type = null): mixed
    {
        $this->navigator = new SerializeGraphNavigator($this->factory, $this->handlerRegistry, $this->dispatcher);

        if ($context === null) {
            $context = new SerializationContext();
        }

        $context->namingStrategy ??= $this->defaultNamingStrategy;

        if (! isset($this->serializationVisitors[$format])) {
            throw new UnsupportedFormatException(sprintf('The format "%s" is not supported for serialization', $format));
        }

        return $this->visit($this->serializationVisitors[$format], $context, $data, $format, $type);
    }

    public function deserialize(mixed $data, Type $type, string $format, DeserializationContext|null $context = null): mixed
    {
        $this->navigator = new DeserializeGraphNavigator($this->factory, $this->handlerRegistry, $this->objectConstructor, $this->dispatcher);

        if ($context === null) {
            $context = new DeserializationContext();
        }

        $context->namingStrategy ??= $this->defaultNamingStrategy;

        if (! isset($this->deserializationVisitors[$format])) {
            throw new UnsupportedFormatException(sprintf('The format "%s" is not supported for deserialization', $format));
        }

        return $this->visit($this->deserializationVisitors[$format], $context, $data, $format, $type);
    }

    /**
     * {@inheritDoc}
     */
    public function normalize($data, SerializationContext|null $context = null): array
    {
        $result = $this->serialize($data, 'array', $context);

        if (! is_array($result)) {
            throw new RuntimeException(sprintf('The input data of type "%s" did not convert to an array, but got a result of type "%s".', get_debug_type($data), get_debug_type($result)));
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function denormalize(array $data, Type $type, DeserializationContext|null $context = null): mixed
    {
        return $this->deserialize($data, $type, 'array', $context);
    }

    public function getMetadataFactory(): MetadataFactoryInterface
    {
        return $this->factory;
    }

    private function visit(VisitorInterface $visitor, Context $context, mixed $data, string $format, Type|null $type): mixed
    {
        $data = $visitor->prepare($data);
        $context->initialize($format, $visitor, $this->navigator, $this->factory);

        $visitor->setNavigator($this->navigator);
        $this->navigator->accept($data, $type, $context);

        return $visitor->getResult();
    }
}
