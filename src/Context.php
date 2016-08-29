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

use Kcs\Metadata\Factory\MetadataFactoryInterface;
use Kcs\Serializer\Exclusion\DepthExclusionStrategy;
use Kcs\Serializer\Exclusion\DisjunctExclusionStrategy;
use Kcs\Serializer\Exclusion\ExclusionStrategyInterface;
use Kcs\Serializer\Exclusion\GroupsExclusionStrategy;
use Kcs\Serializer\Exclusion\VersionExclusionStrategy;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Type\Type;

abstract class Context
{
    /**
     * @var AttributesMap
     */
    public $attributes;

    private $format;

    /** @var VisitorInterface */
    private $visitor;

    /** @var GraphNavigator */
    private $navigator;

    /** @var MetadataFactoryInterface */
    private $metadataFactory;

    /** @var ExclusionStrategyInterface */
    private $exclusionStrategy;

    /** @var bool */
    private $serializeNull = false;

    private $initialized = false;

    /** @var \SplStack */
    private $metadataStack;

    /**
     * @var array
     */
    private $nonSkippedProperties;

    public function __construct()
    {
        $this->attributes = new AttributesMap();
    }

    public function initialize($format, VisitorInterface $visitor, GraphNavigator $navigator, MetadataFactoryInterface $factory)
    {
        if ($this->initialized) {
            throw new \LogicException('This context was already initialized, and cannot be re-used.');
        }

        $this->initialized = true;
        $this->format = $format;
        $this->visitor = $visitor;
        $this->navigator = $navigator;
        $this->metadataFactory = $factory;
        $this->metadataStack = new \SplStack();
        $this->nonSkippedProperties = [];

        $this->addVersionExclusionStrategy();
        $this->addGroupsExclusionStrategy();
    }

    public function accept($data, Type $type = null)
    {
        return $this->navigator->accept($data, $type, $this);
    }

    public function getMetadataFactory()
    {
        return $this->metadataFactory;
    }

    public function getVisitor()
    {
        return $this->visitor;
    }

    public function getNavigator()
    {
        return $this->navigator;
    }

    public function getExclusionStrategy()
    {
        return $this->exclusionStrategy;
    }

    public function setAttribute($key, $value)
    {
        $this->assertMutable();
        $this->attributes->set($key, $value);

        return $this;
    }

    private function assertMutable()
    {
        if (! $this->initialized) {
            return;
        }

        throw new \LogicException('This context was already initialized and is immutable; you cannot modify it anymore.');
    }

    private function assertInitialized()
    {
        if ($this->initialized) {
            return;
        }

        throw new \LogicException('This context is not initialized.');
    }

    public function addExclusionStrategy(ExclusionStrategyInterface $strategy)
    {
        $this->assertMutable();
        $this->_addExclusionStrategy($strategy);

        return $this;
    }

    public function setVersion($version)
    {
        $this->setAttribute('version', $version);

        return $this;
    }

    public function setGroups($groups)
    {
        if (empty($groups)) {
            $groups = null;
        } elseif (! is_array($groups)) {
            $groups = (array) $groups;
        }

        $this->setAttribute('groups', $groups);

        return $this;
    }

    public function enableMaxDepthChecks()
    {
        $this->addExclusionStrategy(new DepthExclusionStrategy());

        return $this;
    }

    public function setSerializeNull($bool)
    {
        $this->serializeNull = !! $bool;

        return $this;
    }

    public function shouldSerializeNull()
    {
        return $this->serializeNull;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function pushPropertyMetadata(PropertyMetadata $metadata)
    {
        $this->metadataStack->push($metadata);
    }

    public function popPropertyMetadata()
    {
        $metadata = $this->metadataStack->pop();

        return $metadata;
    }

    public function getMetadataStack()
    {
        return $this->metadataStack;
    }

    /**
     * Get the array of properties that should be serialized in an object
     *
     * @param ClassMetadata $metadata
     *
     * @return PropertyMetadata[]
     */
    public function getNonSkippedProperties(ClassMetadata $metadata)
    {
        $this->assertInitialized();

        $class = $metadata->getName();
        if (isset($this->nonSkippedProperties[$class])) {
            return $this->nonSkippedProperties[$class];
        }

        $properties = $metadata->getAttributesMetadata();
        if (null !== $this->exclusionStrategy) {
            /** @var PropertyMetadata[] $properties */
            $properties = array_filter(
                $properties,
                function (PropertyMetadata $propertyMetadata) {
                    $this->pushPropertyMetadata($propertyMetadata);
                    $result = ! $this->exclusionStrategy->shouldSkipProperty($propertyMetadata, $this);
                    $this->popPropertyMetadata();

                    return $result;
                }
            );
        }

        return $this->nonSkippedProperties[$class] = $properties;
    }

    abstract public function getDepth();

    /**
     * @return int
     */
    abstract public function getDirection();

    /**
     * Set or add exclusion strategy
     *
     * @param ExclusionStrategyInterface $strategy
     */
    private function _addExclusionStrategy(ExclusionStrategyInterface $strategy)
    {
        if (null === $this->exclusionStrategy) {
            $this->exclusionStrategy = $strategy;

            return;
        }

        if ($this->exclusionStrategy instanceof DisjunctExclusionStrategy) {
            $this->exclusionStrategy->addStrategy($strategy);

            return;
        }

        $this->exclusionStrategy = new DisjunctExclusionStrategy([
            $this->exclusionStrategy,
            $strategy,
        ]);
    }

    private function addVersionExclusionStrategy()
    {
        if (null === ($version = $this->attributes->get('version'))) {
            return;
        }

        $this->_addExclusionStrategy(new VersionExclusionStrategy($version));
    }

    private function addGroupsExclusionStrategy()
    {
        if (null === ($groups = $this->attributes->get('groups'))) {
            return;
        }

        $this->_addExclusionStrategy(new GroupsExclusionStrategy($groups));
    }
}
