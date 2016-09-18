<?php

/*
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

namespace Kcs\Serializer\Metadata;

/**
 * @author Alessandro Chitolina <alekitto@gmail.com>
 */
class MetadataStack implements \IteratorAggregate, \Countable
{
    /**
     * @var \SplStack
     */
    private $stack;

    /**
     * @var string[]
     */
    private $currentPath;

    public function __construct()
    {
        $this->stack = new \SplStack();
        $this->currentPath = [];
    }
    public function push(PropertyMetadata $metadata)
    {
        $this->stack->push($metadata);
        $this->currentPath[] = $metadata->name;
    }

    public function pop()
    {
        $metadata = $this->stack->pop();
        array_pop($this->currentPath);

        return $metadata;
    }

    /**
     * Get current property path
     *
     * @return string[]
     */
    public function getPath()
    {
        return $this->currentPath;
    }

    public function getCurrent()
    {
        return $this->stack->isEmpty() ? null : $this->stack->top();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->stack;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->stack->count();
    }
}
