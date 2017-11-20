<?php declare(strict_types=1);
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

use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;

/**
 * Interface for visitors.
 *
 * This contains the minimal set of values that must be supported for any
 * output format.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface VisitorInterface
{
    /**
     * Allows visitors to convert the input data to a different representation
     * before the actual serialization/deserialization process starts.
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function prepare($data);

    /**
     * @param mixed   $data
     * @param Type    $type
     * @param Context $context
     *
     * @return mixed
     */
    public function visitNull($data, Type $type, Context $context);

    /**
     * @param mixed   $data
     * @param Type    $type
     * @param Context $context
     *
     * @return mixed
     */
    public function visitString($data, Type $type, Context $context);

    /**
     * @param mixed   $data
     * @param Type    $type
     * @param Context $context
     *
     * @return mixed
     */
    public function visitBoolean($data, Type $type, Context $context);

    /**
     * @param mixed   $data
     * @param Type    $type
     * @param Context $context
     *
     * @return mixed
     */
    public function visitDouble($data, Type $type, Context $context);

    /**
     * @param mixed   $data
     * @param Type    $type
     * @param Context $context
     *
     * @return mixed
     */
    public function visitInteger($data, Type $type, Context $context);

    /**
     * @param mixed   $data
     * @param Type    $type
     * @param Context $context
     *
     * @return mixed
     */
    public function visitArray($data, Type $type, Context $context);

    /**
     * @param ClassMetadata $metadata
     * @param $data
     * @param Type                       $type
     * @param Context                    $context
     * @param ObjectConstructorInterface $objectConstructor
     *
     * @return mixed
     */
    public function visitObject(ClassMetadata $metadata, $data, Type $type, Context $context, ObjectConstructorInterface $objectConstructor = null);

    /**
     * @param callable $handler
     * @param $data
     * @param Type    $type
     * @param Context $context
     *
     * @return mixed
     */
    public function visitCustom(callable $handler, $data, Type $type, Context $context);

    /**
     * Called before the properties of the object are being visited.
     *
     * @param mixed   $data
     * @param Type    $type
     * @param Context $context
     */
    public function startVisiting($data, Type $type, Context $context);

    /**
     * Called after all properties of the object have been visited.
     *
     * @param mixed   $data
     * @param Type    $type
     * @param Context $context
     *
     * @return mixed
     */
    public function endVisiting($data, Type $type, Context $context);

    /**
     * Called before serialization/deserialization starts.
     *
     * @param GraphNavigator $navigator
     */
    public function setNavigator(GraphNavigator $navigator = null);

    /**
     * @return mixed
     */
    public function getResult();
}
