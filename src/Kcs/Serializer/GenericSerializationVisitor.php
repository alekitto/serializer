<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 * Modifications copyright (c) 2016 Alessandro Chitolina <alekitto@gmail.com>
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

use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Type\Type;

class GenericSerializationVisitor extends AbstractVisitor
{
    private $navigator;
    private $root;
    private $dataStack;
    private $data;

    public function setNavigator(GraphNavigator $navigator = null)
    {
        $this->navigator = $navigator;
        $this->root = null;
        $this->dataStack = new \SplStack;
    }

    public function visitNull($data, Type $type, Context $context)
    {
        return $this->data = null;
    }

    public function visitString($data, Type $type, Context $context)
    {
        return $this->data = (string) $data;
    }

    public function visitBoolean($data, Type $type, Context $context)
    {
        return $this->data = (boolean) $data;
    }

    public function visitInteger($data, Type $type, Context $context)
    {
        return $this->data = (int) $data;
    }

    public function visitDouble($data, Type $type, Context $context)
    {
        return $this->data = (float) $data;
    }

    public function visitArray($data, Type $type, Context $context)
    {
        $rs = array();
        foreach ($data as $k => $v) {
            $v = $this->navigator->accept($v, $this->getElementType($type), $context);

            if (null === $v && ( ! is_string($k) || ! $context->shouldSerializeNull())) {
                continue;
            }

            $rs[$k] = $v;
        }

        return $this->data = $rs;
    }

    public function visitObject(ClassMetadata $metadata, $data, Type $type, Context $context, ObjectConstructorInterface $objectConstructor = null)
    {
        $this->data = array();

        $exclusionStrategy = $context->getExclusionStrategy();
        if (isset($metadata->handlerCallbacks[$context->getDirection()][$context->getFormat()])) {
            $callback = $metadata->handlerCallbacks[$context->getDirection()][$context->getFormat()];
            return $this->data = $data->$callback($this, null, $context);
        }

        /** @var PropertyMetadata $propertyMetadata */
        foreach ($metadata->getAttributesMetadata() as $propertyMetadata) {
            if (null !== $exclusionStrategy && $exclusionStrategy->shouldSkipProperty($propertyMetadata, $context)) {
                continue;
            }

            $context->pushPropertyMetadata($propertyMetadata);
            $this->visitProperty($propertyMetadata, $data, $context);
            $context->popPropertyMetadata();
        }

        return $this->data;
    }

    public function startVisiting($data, Type $type, Context $context)
    {
        $this->dataStack->push($this->data);
        $this->data = null;
    }

    public function endVisiting($data, Type $type, Context $context)
    {
        $rs = $this->data;
        $this->data = $this->dataStack->pop();

        if (null === $this->root && 0 === $this->dataStack->count()) {
            $this->root = $rs;
        }

        return $rs;
    }

    public function visitProperty(PropertyMetadata $metadata, $data, Context $context)
    {
        $v = $metadata->getValue($data);

        $v = $this->navigator->accept($v, $metadata->type, $context);
        if (null === $v && ! $context->shouldSerializeNull()) {
            return;
        }

        $k = $this->namingStrategy->translateName($metadata);

        if ($metadata->inline) {
            if (is_array($v)) {
                $this->data = array_merge($this->data, $v);
            }
        } else {
            $this->data[$k] = $v;
        }
    }

    public function visitCustom(callable $handler, $data, Type $type, Context $context)
    {
        $args = func_get_args();

        $handler = array_shift($args);
        array_unshift($args, $this);
        return $this->data = call_user_func_array($handler, $args);
    }

    /**
     * Allows you to add additional data to the current object/root element.
     *
     * @param string $key
     * @param mixed $value This value must either be a regular scalar, or an array.
     *                     It must not contain any objects anymore.
     */
    public function addData($key, $value)
    {
        if (isset($this->data[$key])) {
            throw new InvalidArgumentException(sprintf('There is already data for "%s".', $key));
        }

        $this->data[$key] = $value;
    }

    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @param array|\ArrayObject $data the passed data must be understood by whatever encoding function is applied later.
     */
    public function setRoot($data)
    {
        $this->root = $data;
    }

    /**
     * @inheritDoc
     */
    public function getResult()
    {
        return $this->getRoot();
    }

    protected function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @internal
     */
    protected function getData()
    {
        return $this->data;
    }
}
