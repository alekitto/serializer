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

use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Metadata\ClassMetadata;

/**
 * Generic Deserialization Visitor.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
abstract class GenericDeserializationVisitor extends GenericSerializationVisitor
{
    public function visitArray($data, array $type, Context $context)
    {
        if ( ! is_array($data)) {
            throw new RuntimeException(sprintf('Expected array, but got %s: %s', gettype($data), json_encode($data)));
        }

        // If no further parameters were given, keys/values are just passed as is.
        if ( ! $type['params']) {
            $this->setData($data);
            return $data;
        }

        switch (count($type['params'])) {
            case 1: // Array is a list.
                $listType = $type['params'][0];

                $result = array();
                foreach ($data as $v) {
                    $result[] = $this->getNavigator()->accept($v, $listType, $context);
                }

                $this->setData($result);
                return $result;

            case 2: // Array is a map.
                list($keyType, $entryType) = $type['params'];

                $result = array();
                foreach ($data as $k => $v) {
                    $result[$this->getNavigator()->accept($k, $keyType, $context)] = $this->getNavigator()->accept($v, $entryType, $context);
                }

                $this->setData($result);
                return $result;

            default:
                throw new RuntimeException(sprintf('Array type cannot have more than 2 parameters, but got %s.', json_encode($type['params'])));
        }
    }

    public function visitObject(ClassMetadata $metadata, $data, array $type, Context $context, ObjectConstructorInterface $objectConstructor = null)
    {
        $exclusionStrategy = $context->getExclusionStrategy();

        $object = $objectConstructor->construct($this, $metadata, $data, $type, $context);
        if (isset($metadata->handlerCallbacks[$context->getDirection()][$context->getFormat()])) {
            $callback = $metadata->handlerCallbacks[$context->getDirection()][$context->getFormat()];
            $object->$callback($this, $data, $context);

            $this->setData($object);
            return $object;
        }

        /** @var PropertyMetadata $propertyMetadata */
        foreach ($metadata->getAttributesMetadata() as $propertyMetadata) {
            if (null !== $exclusionStrategy && $exclusionStrategy->shouldSkipProperty($propertyMetadata, $context)) {
                continue;
            }

            if ($propertyMetadata->readOnly) {
                continue;
            }

            $context->pushPropertyMetadata($propertyMetadata);
            $v = $this->visitProperty($propertyMetadata, $data, $context);
            $context->popPropertyMetadata();

            if (null === $propertyMetadata->setter) {
                $propertyMetadata->getReflection()->setValue($object, $v);
                continue;
            }

            $object->{$propertyMetadata->setter}($v);
        }

        $this->setData($object);
        return $object;
    }

    public function visitProperty(PropertyMetadata $metadata, $data, Context $context)
    {
        $name = $this->namingStrategy->translateName($metadata);

        if (null === $data || ! array_key_exists($name, $data)) {
            return null;
        }

        if ( ! $metadata->type) {
            throw new RuntimeException(sprintf('You must define a type for %s::$%s.', $metadata->getReflection()->class, $metadata->name));
        }

        $v = $data[$name] !== null ? $this->getNavigator()->accept($data[$name], $metadata->type, $context) : null;

        $this->addData($name, $v);
        return $v;
    }

    public function getResult()
    {
        return $this->getRoot();
    }
}
