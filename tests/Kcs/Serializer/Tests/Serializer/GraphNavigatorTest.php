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

use Doctrine\Common\Annotations\AnnotationReader;
use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Construction\UnserializeObjectConstructor;
use Kcs\Serializer\Context;
use Kcs\Serializer\Direction;
use Kcs\Serializer\GraphNavigator;
use Kcs\Serializer\Handler\HandlerRegistry;
use Kcs\Serializer\Handler\SubscribingHandlerInterface;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\Loader\AnnotationLoader;
use Kcs\Serializer\Metadata\MetadataFactory;
use Kcs\Serializer\Type\Type;
use Symfony\Component\EventDispatcher\EventDispatcher;

class GraphNavigatorTest extends \PHPUnit_Framework_TestCase
{
    private $metadataFactory;
    private $handlerRegistry;
    private $objectConstructor;
    private $dispatcher;
    private $navigator;

    /**
     * @expectedException \Kcs\Serializer\Exception\RuntimeException
     * @expectedExceptionMessage Resources are not supported in serialized data.
     */
    public function testResourceThrowsException()
    {
        $context = $this->getMock('Kcs\Serializer\SerializationContext');

        $context->expects($this->any())
            ->method('getVisitor')
            ->will($this->returnValue($this->getMock('Kcs\Serializer\VisitorInterface')));
        $context->expects($this->any())
            ->method('getDirection')
            ->will($this->returnValue(Direction::DIRECTION_SERIALIZATION));

        $this->navigator->accept(STDIN, null, $context);
    }

    public function testNavigatorPassesInstanceOnSerialization()
    {
        $context = $this->getMock('Kcs\Serializer\SerializationContext');
        $object = new SerializableClass();
        $metadata = $this->metadataFactory->getMetadataFor(get_class($object));

        $self = $this;
        $context->expects($this->any())
            ->method('getDirection')
            ->will($this->returnValue(Direction::DIRECTION_SERIALIZATION));

        $visitor = $this->getMock('Kcs\Serializer\VisitorInterface');
        $visitor->expects($this->any())
            ->method('visitObject')
            ->will($this->returnCallback(function (ClassMetadata $passedMetadata, $data, Type $type, Context $passedContext, ObjectConstructorInterface $objectConstructor) use ($context, $metadata, $self) {
                $self->assertSame($metadata, $passedMetadata);
                $self->assertTrue($context === $passedContext);
            }));

        $context->expects($this->any())
            ->method('getVisitor')
            ->will($this->returnValue($visitor));

        $this->navigator = new GraphNavigator($this->metadataFactory, $this->handlerRegistry, $this->objectConstructor, $this->dispatcher);
        $this->navigator->accept($object, null, $context);
    }

    public function testNavigatorChangeTypeOnSerialization()
    {
        $object = new SerializableClass();
        $typeName = 'JsonSerializable';

        $this->dispatcher->addListener('serializer.pre_serialize', function ($event) use ($typeName) {
            $type = $event->getType();
            $type->setName($typeName);
        });

        $this->handlerRegistry->registerSubscribingHandler(new TestSubscribingHandler());

        $context = $this->getMock('Kcs\Serializer\SerializationContext');
        $context->expects($this->any())
            ->method('getDirection')
            ->will($this->returnValue(Direction::DIRECTION_SERIALIZATION));

        $context->expects($this->any())
            ->method('getVisitor')
            ->will($this->returnValue($this->getMock('Kcs\Serializer\VisitorInterface')));

        $this->navigator = new GraphNavigator($this->metadataFactory, $this->handlerRegistry, $this->objectConstructor, $this->dispatcher);
        $this->navigator->accept($object, null, $context);
    }

    protected function setUp()
    {
        $this->dispatcher = new EventDispatcher();
        $this->handlerRegistry = new HandlerRegistry();
        $this->objectConstructor = new UnserializeObjectConstructor();

        $loader = new AnnotationLoader();
        $loader->setReader(new AnnotationReader());
        $this->metadataFactory = new MetadataFactory($loader);
        $this->navigator = new GraphNavigator($this->metadataFactory, $this->handlerRegistry, $this->objectConstructor, $this->dispatcher);
    }
}

class SerializableClass
{
    public $foo = 'bar';
}

class TestSubscribingHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return [[
            'type' => 'JsonSerializable',
            'direction' => Direction::DIRECTION_SERIALIZATION,
            'method' => 'serialize',
        ]];
    }

    public function serialize()
    {
    }
}
