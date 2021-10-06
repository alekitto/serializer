<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Metadata\Factory\MetadataFactoryInterface;
use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Exception\UnsupportedFormatException;
use Kcs\Serializer\Handler\HandlerRegistryInterface;
use Kcs\Serializer\Type\Type;
use Psr\EventDispatcher\EventDispatcherInterface;

use function get_debug_type;
use function is_array;
use function Safe\sprintf;

class Serializer implements SerializerInterface
{
    private GraphNavigator $navigator;

    /**
     * @param VisitorInterface[] $serializationVisitors
     * @param VisitorInterface[] $deserializationVisitors
     */
    public function __construct(
        private MetadataFactoryInterface $factory,
        private HandlerRegistryInterface $handlerRegistry,
        private ObjectConstructorInterface $objectConstructor,
        private array $serializationVisitors,
        private array $deserializationVisitors,
        private ?EventDispatcherInterface $dispatcher = null
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function serialize(mixed $data, string $format, ?SerializationContext $context = null, ?Type $type = null): mixed
    {
        $this->navigator = new SerializeGraphNavigator($this->factory, $this->handlerRegistry, $this->dispatcher);

        if ($context === null) {
            $context = new SerializationContext();
        }

        if (! isset($this->serializationVisitors[$format])) {
            throw new UnsupportedFormatException(sprintf('The format "%s" is not supported for serialization', $format));
        }

        return $this->visit($this->serializationVisitors[$format], $context, $data, $format, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function deserialize(mixed $data, Type $type, string $format, ?DeserializationContext $context = null): mixed
    {
        $this->navigator = new DeserializeGraphNavigator($this->factory, $this->handlerRegistry, $this->objectConstructor, $this->dispatcher);

        if ($context === null) {
            $context = new DeserializationContext();
        }

        if (! isset($this->deserializationVisitors[$format])) {
            throw new UnsupportedFormatException(sprintf('The format "%s" is not supported for deserialization', $format));
        }

        return $this->visit($this->deserializationVisitors[$format], $context, $data, $format, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($data, ?SerializationContext $context = null): array
    {
        $result = $this->serialize($data, 'array', $context);

        if (! is_array($result)) {
            throw new RuntimeException(sprintf('The input data of type "%s" did not convert to an array, but got a result of type "%s".', get_debug_type($data), get_debug_type($result)));
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize(array $data, Type $type, ?DeserializationContext $context = null): mixed
    {
        return $this->deserialize($data, $type, 'array', $context);
    }

    public function getMetadataFactory(): MetadataFactoryInterface
    {
        return $this->factory;
    }

    private function visit(VisitorInterface $visitor, Context $context, mixed $data, string $format, ?Type $type): mixed
    {
        $data = $visitor->prepare($data);
        $context->initialize($format, $visitor, $this->navigator, $this->factory);

        $visitor->setNavigator($this->navigator);
        $this->navigator->accept($data, $type, $context);

        return $visitor->getResult();
    }
}
