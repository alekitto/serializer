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

namespace Kcs\Serializer\Tests\Serializer;

use Kcs\Metadata\Factory\MetadataFactoryInterface;
use Kcs\Serializer\Exclusion\ExclusionStrategyInterface;
use Kcs\Serializer\GraphNavigator;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\SerializerBuilder;
use Kcs\Serializer\Tests\Fixtures\InlineChild;
use Kcs\Serializer\Tests\Fixtures\Node;
use Kcs\Serializer\VisitorInterface;

class ContextTest extends \PHPUnit_Framework_TestCase
{
    public function testSerializationContextPathAndDepth()
    {
        $object = new Node([
            new Node(),
            new Node([
                new Node(),
            ]),
        ]);
        $objects = [$object, $object->children[0], $object->children[1], $object->children[1]->children[0]];

        $navigator = $this->getMockBuilder(GraphNavigator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context = new SerializationContext();
        $context->initialize(
            'json',
            $this->createMock(VisitorInterface::class),
            $navigator,
            $this->createMock(MetadataFactoryInterface::class)
        );

        $context->startVisiting($objects[0]);
        $this->assertEquals(1, $context->getDepth());
        $context->startVisiting($objects[1]);
        $this->assertEquals(2, $context->getDepth());
        $context->startVisiting($objects[2]);
        $this->assertEquals(3, $context->getDepth());
    }

    public function testSerializationMetadataStack()
    {
        $object = new Node([
            $child = new InlineChild(),
        ]);

        $exclusionStrategy = $this->createMock(ExclusionStrategyInterface::class);
        $exclusionStrategy->expects($this->any())
            ->method('shouldSkipClass')
            ->will($this->returnValue(false));

        $exclusionStrategy->expects($this->any())
            ->method('shouldSkipProperty')
            ->will($this->returnCallback(function (PropertyMetadata $propertyMetadata, SerializationContext $context) use ($object, $child) {
                $stack = $context->getMetadataStack();

                if (Node::class === $propertyMetadata->class && $propertyMetadata->name === 'children') {
                    $this->assertEquals(0, $stack->count());
                }

                if (InlineChild::class === $propertyMetadata->class) {
                    $this->assertEquals(1, $stack->count());
                    $this->assertEquals('children', $stack[0]->getName());
                }

                return false;
            }));

        $serializer = SerializerBuilder::create()->build();
        $serializer->serialize($object, 'json', SerializationContext::create()->addExclusionStrategy($exclusionStrategy));
    }
}
