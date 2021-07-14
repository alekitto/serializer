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
use function sprintf;

class Serializer implements SerializerInterface
{
    private MetadataFactoryInterface $factory;

    /** @var VisitorInterface[] */
    private array $serializationVisitors;

    /** @var VisitorInterface[] */
    private array $deserializationVisitors;

    private GraphNavigator $navigator;
    private HandlerRegistryInterface $handlerRegistry;
    private ObjectConstructorInterface $objectConstructor;
    private ?EventDispatcherInterface $dispatcher;

    public function __construct(
        MetadataFactoryInterface $factory,
        HandlerRegistryInterface $handlerRegistry,
        ObjectConstructorInterface $objectConstructor,
        array $serializationVisitors,
        array $deserializationVisitors,
        ?EventDispatcherInterface $dispatcher = null
    ) {
        $this->factory = $factory;
        $this->serializationVisitors = $serializationVisitors;
        $this->deserializationVisitors = $deserializationVisitors;

        $this->handlerRegistry = $handlerRegistry;
        $this->objectConstructor = $objectConstructor;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($data, string $format, ?SerializationContext $context = null, ?Type $type = null)
    {
        $this->navigator = new SerializeGraphNavigator($this->factory, $this->handlerRegistry, $this->dispatcher);

        if ($context === null) {
            $context = new SerializationContext();
        }

        if (! isset($this->serializationVisitors[$format])) {
            throw new UnsupportedFormatException("The format \"$format\" is not supported for serialization");
        }

        return $this->visit($this->serializationVisitors[$format], $context, $data, $format, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function deserialize($data, Type $type, string $format, ?DeserializationContext $context = null)
    {
        $this->navigator = new DeserializeGraphNavigator($this->factory, $this->handlerRegistry, $this->objectConstructor, $this->dispatcher);

        if ($context === null) {
            $context = new DeserializationContext();
        }

        if (! isset($this->deserializationVisitors[$format])) {
            throw new UnsupportedFormatException("The format \"$format\" is not supported for deserialization");
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
    public function denormalize(array $data, Type $type, ?DeserializationContext $context = null)
    {
        return $this->deserialize($data, $type, 'array', $context);
    }

    public function getMetadataFactory(): MetadataFactoryInterface
    {
        return $this->factory;
    }

    private function visit(VisitorInterface $visitor, Context $context, $data, $format, ?Type $type)
    {
        $data = $visitor->prepare($data);
        $context->initialize($format, $visitor, $this->navigator, $this->factory);

        $visitor->setNavigator($this->navigator);
        $this->navigator->accept($data, $type, $context);

        return $visitor->getResult();
    }
}
