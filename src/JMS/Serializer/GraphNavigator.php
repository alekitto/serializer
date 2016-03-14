<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
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

namespace JMS\Serializer;

use JMS\Serializer\EventDispatcher\PostDeserializeEvent;
use JMS\Serializer\EventDispatcher\PostSerializeEvent;
use JMS\Serializer\EventDispatcher\PreDeserializeEvent;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\EventDispatcher\EventDispatcherInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Exception\InvalidArgumentException;
use Kcs\Metadata\Factory\MetadataFactoryInterface;

/**
 * Handles traversal along the object graph.
 *
 * This class handles traversal along the graph, and calls different methods
 * on visitors, or custom handlers to process its nodes.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
/* final */ class GraphNavigator
{
    const DIRECTION_SERIALIZATION = 1;
    const DIRECTION_DESERIALIZATION = 2;

    private $dispatcher;
    private $metadataFactory;
    private $handlerRegistry;
    private $objectConstructor;

    /**
     * Parses a direction string to one of the direction constants.
     *
     * @param string $dirStr
     *
     * @return integer
     */
    public static function parseDirection($dirStr)
    {
        switch (strtolower($dirStr)) {
            case 'serialization':
                return self::DIRECTION_SERIALIZATION;

            case 'deserialization':
                return self::DIRECTION_DESERIALIZATION;

            default:
                throw new InvalidArgumentException(sprintf('The direction "%s" does not exist.', $dirStr));
        }
    }

    public function __construct(MetadataFactoryInterface $metadataFactory, HandlerRegistryInterface $handlerRegistry, ObjectConstructorInterface $objectConstructor, EventDispatcherInterface $dispatcher = null)
    {
        $this->dispatcher = $dispatcher;
        $this->metadataFactory = $metadataFactory;
        $this->handlerRegistry = $handlerRegistry;
        $this->objectConstructor = $objectConstructor;
    }

    /**
     * Called for each node of the graph that is being traversed.
     *
     * @param mixed $data the data depends on the direction, and type of visitor
     * @param null|array $type array has the format ["name" => string, "params" => array]
     *
     * @return mixed the return value depends on the direction, and type of visitor
     */
    public function accept($data, array $type = null, Context $context)
    {
        // If the data is null, we have to force the type to null regardless of the input in order to
        // guarantee correct handling of null values, and not have any internal auto-casting behavior.
        if ($context instanceof SerializationContext && null === $data) {
            $type = array('name' => 'NULL', 'params' => array());
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

    private function guessType($data)
    {
        // infer the most specific type from the input data
        $typeName = gettype($data);
        if ('object' === $typeName) {
            $typeName = get_class($data);
        }

        return array('name' => $typeName, 'params' => array());
    }

    private function resolveMetadata(DeserializationContext $context, $data, ClassMetadata $metadata)
    {
        switch (true) {
            case is_array($data) && isset($data[$metadata->discriminatorFieldName]):
                $typeValue = (string) $data[$metadata->discriminatorFieldName];
                break;

            case is_object($data) && isset($data->{$metadata->discriminatorFieldName}):
                $typeValue = (string) $data->{$metadata->discriminatorFieldName};
                break;

            default:
                throw new \LogicException(sprintf(
                    'The discriminator field name "%s" for base-class "%s" was not found in input data.',
                    $metadata->discriminatorFieldName,
                    $metadata->getName()
                ));
        }

        if ( ! isset($metadata->discriminatorMap[$typeValue])) {
            throw new \LogicException(sprintf(
                'The type value "%s" does not exist in the discriminator map of class "%s". Available types: %s',
                $typeValue,
                $metadata->getName(),
                implode(', ', array_keys($metadata->discriminatorMap))
            ));
        }

        return $this->metadataFactory->getMetadataFor($metadata->discriminatorMap[$typeValue]);
    }

    private function serialize($data, array $type, SerializationContext $context)
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
        if (class_exists($type['name'], false) || interface_exists($type['name'], false)) {
            if (is_subclass_of($data, $type['name'], false)) {
                $type = array('name' => get_class($data), 'params' => array());
            }
        }

        if (null !== $this->dispatcher) {
            $this->dispatcher->dispatch('serializer.pre_serialize', $type['name'], $context->getFormat(), $event = new PreSerializeEvent($context, $data, $type));
            $type = $event->getType();
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

        if (null !== $this->dispatcher) {
            $this->dispatcher->dispatch('serializer.post_serialize', $type['name'], $context->getFormat(), new PostSerializeEvent($context, $data, $type));
        }

        $rs = $context->getVisitor()->endVisiting($data, $type, $context);

        if ($inVisitingStack) {
            $context->stopVisiting($data);
        }

        return $rs;
    }

    private function deserialize($data, array $type, DeserializationContext $context)
    {
        $context->increaseDepth();

        if (null !== $this->dispatcher) {
            $this->dispatcher->dispatch('serializer.pre_deserialize', $type['name'], $context->getFormat(), $event = new PreDeserializeEvent($context, $data, $type));
            $type = $event->getType();
            $data = $event->getData();
        }

        $metadata = $this->getMetadataForType($type);
        if (null !== $metadata) {
            if (! empty($metadata->discriminatorMap) && $type['name'] === $metadata->discriminatorBaseClass) {
                $metadata = $this->resolveMetadata($context, $data, $metadata);
            }
        }

        $context->getVisitor()->startVisiting($data, $type, $context);
        $rs = $this->callVisitor($data, $type, $context, $metadata);

        if (null !== $metadata) {
            foreach ($metadata->postDeserializeMethods as $method) {
                $method->getReflection()->invoke($rs);
            }
        }

        if (null !== $this->dispatcher) {
            $this->dispatcher->dispatch('serializer.post_deserialize', $type['name'], $context->getFormat(), new PostDeserializeEvent($context, $rs, $type));
        }

        $rs = $context->getVisitor()->endVisiting($rs, $type, $context);
        $context->decreaseDepth();

        return $rs;
    }

    private function callVisitor($data, array $type, Context $context, ClassMetadata $metadata = null)
    {
        $visitor = $context->getVisitor();

        // First, try whether a custom handler exists for the given type
        if (null !== $handler = $this->handlerRegistry->getHandler($context->getDirection(), $type['name'], $context->getFormat())) {
            return $visitor->visitCustom($handler, $data, $type, $context);
        }

        switch ($type['name']) {
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
                return $visitor->visitArray($data, $type, $context);

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
     * @param array $type
     *
     * @return null|ClassMetadata
     */
    private function getMetadataForType(array $type)
    {
        if (!class_exists($type['name'], false) && !interface_exists($type['name'], false)) {
            return null;
        }

        return $this->metadataFactory->getMetadataFor($type['name']);
    }
}
