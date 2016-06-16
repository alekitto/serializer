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

namespace Kcs\Serializer\Handler;

use Symfony\Component\DependencyInjection\ContainerInterface;

class LazyHandlerRegistry extends HandlerRegistry
{
    private $container;
    private $initializedHandlers = array();

    public function __construct(ContainerInterface $container, array $handlers = array())
    {
        parent::__construct($handlers);
        $this->container = $container;
    }

    public function registerHandler($direction, $typeName, callable $handler)
    {
        parent::registerHandler($direction, $typeName, $handler);
        unset($this->initializedHandlers[$direction][$typeName]);
    }

    public function getHandler($direction, $typeName)
    {
        if (isset($this->initializedHandlers[$direction][$typeName])) {
            return $this->initializedHandlers[$direction][$typeName];
        }

        if ( ! isset($this->handlers[$direction][$typeName])) {
            return null;
        }

        $handler = $this->handlers[$direction][$typeName];
        if (is_array($handler) && is_string($handler[0]) && $this->container->has($handler[0])) {
            $handler[0] = $this->container->get($handler[0]);
        }

        return $this->initializedHandlers[$direction][$typeName] = $handler;
    }
}
