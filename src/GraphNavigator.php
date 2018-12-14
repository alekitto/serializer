<?php declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Metadata\Factory\MetadataFactoryInterface;
use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\EventDispatcher\Events;
use Kcs\Serializer\EventDispatcher\PostDeserializeEvent;
use Kcs\Serializer\EventDispatcher\PostSerializeEvent;
use Kcs\Serializer\EventDispatcher\PreDeserializeEvent;
use Kcs\Serializer\EventDispatcher\PreSerializeEvent;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Handler\HandlerRegistryInterface;
use Kcs\Serializer\Metadata\AdditionalPropertyMetadata;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Handles traversal along the object graph.
 *
 * This class handles traversal along the graph, and calls different methods
 * on visitors, or custom handlers to process its nodes.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 * @author Alessandro Chitolina <alekitto@gmail.com>
 */
class GraphNavigator
{
    private const BUILTIN_TYPES = [
        'NULL' => true,
        'string' => true,
        'integer' => true,
        'int' => true,
        'boolean' => true,
        'bool' => true,
        'double' => true,
        'float' => true,
        'array' => true,
        'resource' => true,
    ];

    /**
     * @var null|EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var HandlerRegistryInterface
     */
    private $handlerRegistry;

    /**
     * @var ObjectConstructorInterface
     */
    private $objectConstructor;

    public function __construct(
        MetadataFactoryInterface $metadataFactory,
        HandlerRegistryInterface $handlerRegistry,
        ObjectConstructorInterface $objectConstructor,
        ?EventDispatcherInterface $dispatcher = null
    ) {
        $this->dispatcher = $dispatcher;
        $this->metadataFactory = $metadataFactory;
        $this->handlerRegistry = $handlerRegistry;
        $this->objectConstructor = $objectConstructor;
    }

    /**
     * Called for each node of the graph that is being traversed.
     *
     * @param mixed                                               $data    the data depends on the direction, and type of visitor
     * @param Type|null                                           $type    array has the format ["name" => string, "params" => array]
     * @param SerializationContext|DeserializationContext|Context $context
     *
     * @return mixed the return value depends on the direction, and type of visitor
     */
    public function accept($data, ?Type $type = null, Context $context)
    {
        if (null === $type) {
            $type = $context->guessType($data);
        }

        if ($context instanceof SerializationContext) {
            return $this->serialize($data, $type, $context);
        }

        return $this->deserialize($data, $type, $context);
    }

    private function serialize($data, Type $type, SerializationContext $context)
    {
        if (null === $data) {
            $type = Type::null();
        }

        if ($inVisitingStack = \is_object($data)) {
            if ($context->isVisiting($data) && ! $context->getMetadataStack()->getCurrent() instanceof AdditionalPropertyMetadata) {
                return null;
            }

            $context->startVisiting($data);
        }

        // If we're serializing a polymorphic type, then we'll be interested in the
        // metadata for the actual type of the object, not the base class.
        if (\is_object($data) && \is_subclass_of($data, $type->getName(), false)) {
            $type = new Type(\get_class($data), $type->getParams());
        }

        if (null !== $this->dispatcher && ! \is_scalar($data)) {
            $this->dispatcher->dispatch(Events::PRE_SERIALIZE, $event = new PreSerializeEvent($context, $data, $type));
            $data = $event->getData();
        }

        if (null !== ($metadata = $this->getMetadataForType($type))) {
            foreach ($metadata->preSerializeMethods as $method) {
                $method->getReflection()->invoke($data);
            }
        }

        $visitor = $context->getVisitor();
        $visitor->startVisiting($data, $type, $context);
        $this->callVisitor($data, $type, $context, $metadata);

        if (null !== $metadata) {
            foreach ($metadata->postSerializeMethods as $method) {
                $method->getReflection()->invoke($data);
            }
        }

        if (null !== $this->dispatcher && ! \is_scalar($data)) {
            $this->dispatcher->dispatch(Events::POST_SERIALIZE, new PostSerializeEvent($context, $data, $type));
        }

        $rs = $visitor->endVisiting($data, $type, $context);
        if ($inVisitingStack) {
            $context->stopVisiting($data);
        }

        return $rs;
    }

    private function deserialize($data, Type $type, DeserializationContext $context)
    {
        $context->increaseDepth();

        if (null !== $this->dispatcher && ! \is_scalar($data)) {
            $this->dispatcher->dispatch(Events::PRE_DESERIALIZE, $event = new PreDeserializeEvent($context, $data, $type));
            $data = $event->getData();
        }

        $metadata = $this->getMetadataForType($type);
        if (null !== $metadata) {
            if (! empty($metadata->discriminatorMap) && $type->is($metadata->discriminatorBaseClass)) {
                $metadata = $this->metadataFactory->getMetadataFor($metadata->getSubtype($data));
            }
        }

        $context->getVisitor()->startVisiting($data, $type, $context);
        $rs = $this->callVisitor($data, $type, $context, $metadata);

        if (null !== $metadata) {
            foreach ($metadata->postDeserializeMethods as $method) {
                $method->getReflection()->invoke($rs);
            }
        }

        if (null !== $this->dispatcher && ! \is_scalar($data)) {
            $this->dispatcher->dispatch(Events::POST_DESERIALIZE, new PostDeserializeEvent($context, $rs, $type));
        }

        $rs = $context->getVisitor()->endVisiting($rs, $type, $context);
        $context->decreaseDepth();

        return $rs;
    }

    private function callVisitor($data, Type $type, Context $context, ClassMetadata $metadata = null)
    {
        $visitor = $context->getVisitor();

        // First, try whether a custom handler exists for the given type
        if (null !== $handler = $this->handlerRegistry->getHandler($context->direction, $type->getName())) {
            return $visitor->visitCustom($handler, $data, $type, $context);
        }

        switch ($type->getName()) {
            case 'NULL':
                return $visitor->visitNull($data, $type, $context);

            case 'string':
                return $visitor->visitString($data, $type, $context);

            case 'integer':
            case 'int':
                return $visitor->visitInteger($data, $type, $context);

            case 'boolean':
            case 'bool':
                return $visitor->visitBoolean($data, $type, $context);

            case 'double':
            case 'float':
                return $visitor->visitDouble($data, $type, $context);

            case 'array':
                return $this->visitArray($visitor, $data, $type, $context);

            case 'resource':
                $msg = 'Resources are not supported in serialized data.';
                throw new RuntimeException($msg);
            default:
                if (null === $metadata) {
                    // Missing handler for custom type
                    return null;
                }

                $exclusionStrategy = $context->getExclusionStrategy();
                if (null !== $exclusionStrategy && $exclusionStrategy->shouldSkipClass($metadata, $context)) {
                    return null;
                }

                return $visitor->visitObject($metadata, $data, $type, $context, $this->objectConstructor);
        }
    }

    /**
     * Get ClassMetadata instance for type. Returns null if class does not exist.
     *
     * @param Type $type
     *
     * @return null|ClassMetadata
     */
    private function getMetadataForType(Type $type): ?ClassMetadata
    {
        if ($metadata = $type->getMetadata()) {
            return $metadata;
        }

        $name = $type->getName();
        if (isset(self::BUILTIN_TYPES[$name]) || (! \class_exists($name) && ! \interface_exists($name))) {
            return null;
        }

        $metadata = $this->metadataFactory->getMetadataFor($name);
        $type->setMetadata($metadata);

        return $metadata;
    }

    private function visitArray(VisitorInterface $visitor, $data, Type $type, Context $context)
    {
        if ($context instanceof SerializationContext && $type->hasParam(0) && ! $type->hasParam(1)) {
            $data = \array_values($data);
        }

        return $visitor->visitArray($data, $type, $context);
    }
}
