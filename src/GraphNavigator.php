<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 * Copyright 2016 Alessandro Chitolina <alekitto@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Kcs\Serializer;

use Kcs\Metadata\Factory\MetadataFactoryInterface;
use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\EventDispatcher\Events;
use Kcs\Serializer\EventDispatcher\PostDeserializeEvent;
use Kcs\Serializer\EventDispatcher\PostSerializeEvent;
use Kcs\Serializer\EventDispatcher\PreDeserializeEvent;
use Kcs\Serializer\EventDispatcher\PreSerializeEvent;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Handler\AdditionalFieldRegistry;
use Kcs\Serializer\Handler\HandlerRegistryInterface;
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
    private $dispatcher;
    private $metadataFactory;
    private $handlerRegistry;
    private $additionalFieldRegistry;
    private $objectConstructor;

    public function __construct(MetadataFactoryInterface $metadataFactory, HandlerRegistryInterface $handlerRegistry, AdditionalFieldRegistry $additionalFieldRegistry, ObjectConstructorInterface $objectConstructor, EventDispatcherInterface $dispatcher = null)
    {
        $this->dispatcher = $dispatcher;
        $this->metadataFactory = $metadataFactory;
        $this->handlerRegistry = $handlerRegistry;
        $this->additionalFieldRegistry = $additionalFieldRegistry;
        $this->objectConstructor = $objectConstructor;
    }

    /**
     * Called for each node of the graph that is being traversed.
     *
     * @param mixed $data the data depends on the direction, and type of visitor
     * @param Type $type array has the format ["name" => string, "params" => array]
     * @param SerializationContext|DeserializationContext|Context $context
     *
     * @return mixed the return value depends on the direction, and type of visitor
     */
    public function accept($data, Type $type = null, Context $context)
    {
        // If the data is null, we have to force the type to null regardless of the input in order to
        // guarantee correct handling of null values, and not have any internal auto-casting behavior.
        if ($context instanceof SerializationContext && null === $data) {
            $type = Type::null();
        } elseif (null === $type) {
            if ($context instanceof DeserializationContext) {
                throw new RuntimeException('The type must be given for all properties when deserializing.');
            }

            $type = $this->guessType($data);
        }

        if ($context instanceof SerializationContext) {
            return $this->serialize($data, $type, $context);
        }

        return $this->deserialize($data, $type, $context);
    }

    public function getAdditionalFieldValue($object, $name)
    {
        return $this->additionalFieldRegistry->getValue($object, $name);
    }

    private function guessType($data)
    {
        return new Type(is_object($data) ? get_class($data) : gettype($data));
    }

    private function serialize($data, Type $type, SerializationContext $context)
    {
        $inVisitingStack = is_object($data) && null !== $data;
        if ($inVisitingStack) {
            if ($context->isVisiting($data)) {
                return null;
            }

            $context->startVisiting($data);
        }

        // If we're serializing a polymorphic type, then we'll be interested in the
        // metadata for the actual type of the object, not the base class.
        if (is_subclass_of($data, $type->getName(), false)) {
            $type = new Type(get_class($data));
        }

        if (null !== $this->dispatcher && ! is_scalar($data)) {
            $this->dispatcher->dispatch(Events::PRE_SERIALIZE, $event = new PreSerializeEvent($context, $data, $type));
            $data = $event->getData();
        }

        $metadata = $this->getMetadataForType($type);
        if (null !== $metadata) {
            foreach ($metadata->preSerializeMethods as $method) {
                $method->getReflection()->invoke($data);
            }
        }

        $context->getVisitor()->startVisiting($data, $type, $context);
        $this->callVisitor($data, $type, $context, $metadata);

        if (null !== $metadata) {
            foreach ($metadata->postSerializeMethods as $method) {
                $method->getReflection()->invoke($data);
            }
        }

        if (null !== $this->dispatcher && ! is_scalar($data)) {
            $this->dispatcher->dispatch(Events::POST_SERIALIZE, new PostSerializeEvent($context, $data, $type));
        }

        $rs = $context->getVisitor()->endVisiting($data, $type, $context);

        if ($inVisitingStack) {
            $context->stopVisiting($data);
        }

        return $rs;
    }

    private function deserialize($data, Type $type, DeserializationContext $context)
    {
        $context->increaseDepth();

        if (null !== $this->dispatcher && ! is_scalar($data)) {
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

        if (null !== $this->dispatcher && ! is_scalar($data)) {
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
        if (null !== $handler = $this->handlerRegistry->getHandler($context->getDirection(), $type->getName())) {
            return $visitor->visitCustom($handler, $data, $type, $context);
        }

        switch ($type->getName()) {
            case 'NULL':
                return $visitor->visitNull($data, $type, $context);

            case 'string':
                return $visitor->visitString($data, $type, $context);

            case 'integer':
                return $visitor->visitInteger($data, $type, $context);

            case 'boolean':
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
                $exclusionStrategy = $context->getExclusionStrategy();

                if (null !== $exclusionStrategy && $exclusionStrategy->shouldSkipClass($metadata, $context)) {
                    return null;
                }

                return $visitor->visitObject($metadata, $data, $type, $context, $this->objectConstructor);
        }
    }

    /**
     * Get ClassMetadata instance for type. Returns null if class does not exist
     *
     * @param Type $type
     *
     * @return null|ClassMetadata
     */
    private function getMetadataForType(Type $type)
    {
        if ($metadata = $type->getMetadata()) {
            return $metadata;
        }

        if (!class_exists($type->getName(), false) && !interface_exists($type->getName(), false)) {
            return null;
        }

        $metadata = $this->metadataFactory->getMetadataFor($type->getName());
        $type->setMetadata($metadata);

        return $metadata;
    }

    private function visitArray(VisitorInterface $visitor, $data, Type $type, $context)
    {
        if ($context instanceof SerializationContext && $type->hasParam(0) && ! $type->hasParam(1)) {
            $data = array_values($data);
        }

        return $visitor->visitArray($data, $type, $context);
    }
}
