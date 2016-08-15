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

use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\SerializerBuilder;
use Kcs\Serializer\Tests\Fixtures\InlineChild;
use Kcs\Serializer\Tests\Fixtures\Node;

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

        $navigator = $this->getMockBuilder('Kcs\Serializer\GraphNavigator')
            ->disableOriginalConstructor()
            ->getMock();

        $context = new SerializationContext();
        $context->initialize(
            'json',
            $this->getMock('Kcs\Serializer\VisitorInterface'),
            $navigator,
            $this->getMock('Kcs\Metadata\Factory\MetadataFactoryInterface')
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
        $self = $this;

        $exclusionStrategy = $this->getMock('Kcs\Serializer\Exclusion\ExclusionStrategyInterface');
        $exclusionStrategy->expects($this->any())
            ->method('shouldSkipClass')
            ->will($this->returnValue(false));

        $exclusionStrategy->expects($this->any())
            ->method('shouldSkipProperty')
            ->will($this->returnCallback(function (PropertyMetadata $propertyMetadata, SerializationContext $context) use ($self, $object, $child) {
                $stack = $context->getMetadataStack();

                if ('Kcs\Serializer\Tests\Fixtures\Node' === $propertyMetadata->class && $propertyMetadata->name === 'children') {
                    $self->assertEquals(0, $stack->count());
                }

                if ('Kcs\Serializer\Tests\Fixtures\InlineChild' === $propertyMetadata->class) {
                    $self->assertEquals(1, $stack->count());
                    $self->assertEquals('children', $stack[0]->getName());
                }

                return false;
            }));

        $serializer = SerializerBuilder::create()->build();
        $serializer->serialize($object, 'json', SerializationContext::create()->addExclusionStrategy($exclusionStrategy));
    }
}
