<?php declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Metadata\Factory\MetadataFactoryInterface;
use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Exception\UnsupportedFormatException;
use Kcs\Serializer\Handler\HandlerRegistryInterface;
use Kcs\Serializer\Type\Type;
use Psr\EventDispatcher\EventDispatcherInterface;

class Serializer implements SerializerInterface
{
    /**
     * @var MetadataFactoryInterface
     */
    private $factory;

    /**
     * @var VisitorInterface[]
     */
    private $serializationVisitors;

    /**
     * @var VisitorInterface[]
     */
    private $deserializationVisitors;

    /**
     * @var GraphNavigator
     */
    private $navigator;

    /**
     * @var HandlerRegistryInterface
     */
    private $handlerRegistry;

    /**
     * @var ObjectConstructorInterface
     */
    private $objectConstructor;

    /**
     * @var EventDispatcherInterface|null
     */
    private $dispatcher;

    /**
     * Constructor.
     *
     * @param MetadataFactoryInterface                $factory
     * @param Handler\HandlerRegistryInterface        $handlerRegistry
     * @param Construction\ObjectConstructorInterface $objectConstructor
     * @param VisitorInterface[]                      $serializationVisitors   of VisitorInterface
     * @param VisitorInterface[]                      $deserializationVisitors of VisitorInterface
     * @param EventDispatcherInterface                $dispatcher
     */
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

        if (null === $context) {
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

        if (null === $context) {
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
    public function normalize($data, SerializationContext $context = null): array
    {
        $result = $this->serialize($data, 'array', $context);

        if (! \is_array($result)) {
            throw new RuntimeException(\sprintf(
                'The input data of type "%s" did not convert to an array, but got a result of type "%s".',
                \is_object($data) ? \get_class($data) : \gettype($data),
                \is_object($result) ? \get_class($result) : \gettype($result)
            ));
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize(array $data, Type $type, DeserializationContext $context = null)
    {
        return $this->deserialize($data, $type, 'array', $context);
    }

    private function visit(VisitorInterface $visitor, Context $context, $data, $format, Type $type = null)
    {
        $data = $visitor->prepare($data);
        $context->initialize($format, $visitor, $this->navigator, $this->factory);

        $visitor->setNavigator($this->navigator);
        $this->navigator->accept($data, $type, $context);

        return $visitor->getResult();
    }

    /**
     * @return MetadataFactoryInterface
     */
    public function getMetadataFactory(): MetadataFactoryInterface
    {
        return $this->factory;
    }
}
