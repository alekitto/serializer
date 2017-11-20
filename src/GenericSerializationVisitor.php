<?php declare(strict_types=1);

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 * Copyright 2017 Alessandro Chitolina <alekitto@gmail.com>
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
use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Type\Type;

class GenericSerializationVisitor extends AbstractVisitor
{
    /**
     * @var GraphNavigator
     */
    private $navigator;
    private $root;
    private $dataStack;
    private $data;

    public function setNavigator(GraphNavigator $navigator = null)
    {
        $this->navigator = $navigator;
        $this->root = null;
        $this->dataStack = new \SplStack();
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
        return $this->data = (bool) $data;
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
        $rs = [];
        $elementType = $this->getElementType($type);

        foreach ($data as $k => $v) {
            $v = $this->navigator->accept($v, $elementType, $context);

            if (null === $v && (! is_string($k) || ! $context->shouldSerializeNull())) {
                continue;
            }

            $rs[$k] = $v;
        }

        return $this->data = $rs;
    }

    public function visitObject(ClassMetadata $metadata, $data, Type $type, Context $context, ObjectConstructorInterface $objectConstructor = null)
    {
        $this->data = [];

        /** @var PropertyMetadata $propertyMetadata */
        foreach ($context->getNonSkippedProperties($metadata) as $propertyMetadata) {
            $context->getMetadataStack()->push($propertyMetadata);
            $this->visitProperty($propertyMetadata, $data, $context);
            $context->getMetadataStack()->pop();
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

    public function visitCustom(callable $handler, $data, Type $type, Context $context)
    {
        return $this->data = parent::visitCustom($handler, $data, $type, $context);
    }

    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @param array|\ArrayObject $data the passed data must be understood by whatever encoding function is applied later
     */
    public function setRoot($data)
    {
        $this->root = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getResult()
    {
        return $this->getRoot();
    }

    protected function visitProperty(PropertyMetadata $metadata, $data, Context $context)
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

    /**
     * Allows you to add additional data to the current object/root element.
     *
     * @param string $key
     * @param mixed  $value This value must either be a regular scalar, or an array.
     *                      It must not contain any objects anymore.
     */
    protected function addData($key, $value)
    {
        if (isset($this->data[$key])) {
            throw new InvalidArgumentException(sprintf('There is already data for "%s".', $key));
        }

        $this->data[$key] = $value;
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
