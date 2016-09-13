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

use Kcs\Serializer\Naming\PropertyNamingStrategyInterface;
use Kcs\Serializer\Type\Type;

abstract class AbstractVisitor implements VisitorInterface
{
    protected $namingStrategy;

    public function __construct(PropertyNamingStrategyInterface $namingStrategy)
    {
        $this->namingStrategy = $namingStrategy;
        $this->setNavigator(null);
    }

    public function getNamingStrategy()
    {
        return $this->namingStrategy;
    }

    public function prepare($data)
    {
        return $data;
    }

    public function visitCustom(callable $handler, $data, Type $type, Context $context)
    {
        return $handler($this, $data, $type, $context);
    }

    protected function getElementType(Type $type)
    {
        if (0 === $type->countParams()) {
            return null;
        }

        $params = $type->getParams();
        if (isset($params[1]) && $params[1] instanceof Type) {
            return $params[1];
        }

        return $params[0];
    }
}
