<?php declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Metadata\Factory\MetadataFactoryInterface;
use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\EventDispatcher\PostDeserializeEvent;
use Kcs\Serializer\EventDispatcher\PreDeserializeEvent;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Handler\HandlerRegistryInterface;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;
use Psr\EventDispatcher\EventDispatcherInterface;

class DeserializeGraphNavigator extends GraphNavigator
{
    private ObjectConstructorInterface $objectConstructor;

    public function __construct(MetadataFactoryInterface $metadataFactory, HandlerRegistryInterface $handlerRegistry, ObjectConstructorInterface $objectConstructor, ?EventDispatcherInterface $dispatcher = null)
    {
        parent::__construct($metadataFactory, $handlerRegistry, $dispatcher);

        $this->objectConstructor = $objectConstructor;
    }

    /**
     * {@inheritdoc}
     *
     * @param DeserializationContext $context
     */
    public function accept($data, ?Type $type, Context $context)
    {
        if (null === $type) {
            throw new RuntimeException('The type must be given for all properties when deserializing.');
        }

        return $this->deserialize($data, $type, $context);
    }

    private function deserialize($data, Type $type, DeserializationContext $context)
    {
        $context->increaseDepth();

        if (null !== $this->dispatcher && ! \is_scalar($data)) {
            $this->dispatcher->dispatch($event = new PreDeserializeEvent($context, $data, $type));
            $data = $event->getData();
        }

        $metadata = $this->getMetadataForType($type);
        if (null !== $metadata && ! empty($metadata->discriminatorMap) && $type->is($metadata->discriminatorBaseClass)) {
            $metadata = $this->metadataFactory->getMetadataFor($metadata->getSubtype($data));
        }

        $context->visitor->startVisiting($data, $type, $context);
        $rs = $this->callVisitor($data, $type, $context, $metadata);

        if (null !== $this->dispatcher && ! \is_scalar($data)) {
            $this->dispatcher->dispatch(new PostDeserializeEvent($context, $rs, $type));
        }

        $rs = $context->visitor->endVisiting($rs, $type, $context);
        $context->decreaseDepth();

        return $rs;
    }

    protected function visitObject(ClassMetadata $metadata, $data, Type $type, Context $context)
    {
        return $context->visitor->visitObject($metadata, $data, $type, $context, $this->objectConstructor);
    }
}
