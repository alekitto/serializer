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

namespace JMS\Serializer\Tests\Serializer;

use JMS\Serializer\Context;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Tests\Fixtures\InlineChild;
use JMS\Serializer\Tests\Fixtures\Node;
use JMS\Serializer\SerializerBuilder;

class ContextTest extends \PHPUnit_Framework_TestCase
{
    public function testSerializationContextPathAndDepth()
    {
        $object = new Node(array(
            new Node(),
            new Node(array(
                new Node()
            )),
        ));
        $objects = array($object, $object->children[0], $object->children[1], $object->children[1]->children[0]);

        $context = new SerializationContext();
        $context->initialize(
            'json',
            $this->getMock('JMS\Serializer\VisitorInterface'),
            $this->getMockWithoutInvokingTheOriginalConstructor('JMS\Serializer\GraphNavigator'),
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
        $object = new Node(array(
            $child = new InlineChild(),
        ));
        $self = $this;

        $exclusionStrategy = $this->getMock('JMS\Serializer\Exclusion\ExclusionStrategyInterface');
        $exclusionStrategy->expects($this->any())
            ->method('shouldSkipClass')
            ->will($this->returnValue(false));

        $exclusionStrategy->expects($this->any())
            ->method('shouldSkipProperty')
            ->will($this->returnCallback(function (PropertyMetadata $propertyMetadata, SerializationContext $context) use ($self, $object, $child) {
                $stack = $context->getMetadataStack();

                if ('JMS\Serializer\Tests\Fixtures\Node' === $propertyMetadata->class && $propertyMetadata->name === 'children') {
                    $self->assertEquals(0, $stack->count());
                }

                if ('JMS\Serializer\Tests\Fixtures\InlineChild' === $propertyMetadata->class) {
                    $self->assertEquals(1, $stack->count());
                    $self->assertEquals('children', $stack[0]->getName());
                }

                return false;
            }));

        $serializer = SerializerBuilder::create()->build();
        $serializer->serialize($object, 'json', SerializationContext::create()->addExclusionStrategy($exclusionStrategy));
    }
}
