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

use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\Construction\UnserializeObjectConstructor;
use JMS\Serializer\Context;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use Doctrine\Common\Annotations\AnnotationReader;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\Loader\AnnotationLoader;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Metadata\MetadataFactory;

class GraphNavigatorTest extends \PHPUnit_Framework_TestCase
{
    private $metadataFactory;
    private $handlerRegistry;
    private $objectConstructor;
    private $dispatcher;
    private $navigator;

    /**
     * @expectedException JMS\Serializer\Exception\RuntimeException
     * @expectedExceptionMessage Resources are not supported in serialized data.
     */
    public function testResourceThrowsException()
    {
        $context = $this->getMock('JMS\Serializer\SerializationContext');

        $context->expects($this->any())
            ->method('getVisitor')
            ->will($this->returnValue($this->getMock('JMS\Serializer\VisitorInterface')));
        $context->expects($this->any())
            ->method('getDirection')
            ->will($this->returnValue(GraphNavigator::DIRECTION_SERIALIZATION));

        $this->navigator->accept(STDIN, null, $context);
    }

    public function testNavigatorPassesInstanceOnSerialization()
    {
        $context = $this->getMock('JMS\Serializer\SerializationContext');
        $object = new SerializableClass;
        $metadata = $this->metadataFactory->getMetadataFor(get_class($object));

        $self = $this;
        $context->expects($this->any())
            ->method('getDirection')
            ->will($this->returnValue(GraphNavigator::DIRECTION_SERIALIZATION));

        $visitor = $this->getMock('JMS\Serializer\VisitorInterface');
        $visitor->expects($this->any())
            ->method('visitObject')
            ->will($this->returnCallback(function(ClassMetadata $passedMetadata, $data, array $type, Context $passedContext, ObjectConstructorInterface $objectConstructor)
                use ($context, $metadata, $self) {
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
        $object = new SerializableClass;
        $typeName = 'JsonSerializable';

        $this->dispatcher->addListener('serializer.pre_serialize', function($event) use ($typeName) {
            $type = $event->getType();
            $type['name'] = $typeName;
            $event->setType($type['name'], $type['params']);
        });

        $this->handlerRegistry->registerSubscribingHandler(new TestSubscribingHandler());

        $context = $this->getMock('JMS\Serializer\SerializationContext');
        $context->expects($this->any())
            ->method('getDirection')
            ->will($this->returnValue(GraphNavigator::DIRECTION_SERIALIZATION));

        $context->expects($this->any())
            ->method('getVisitor')
            ->will($this->returnValue($this->getMock('JMS\Serializer\VisitorInterface')));

        $this->navigator = new GraphNavigator($this->metadataFactory, $this->handlerRegistry, $this->objectConstructor, $this->dispatcher);
        $this->navigator->accept($object, null, $context);
    }

    protected function setUp()
    {
        $this->dispatcher = new EventDispatcher();
        $this->handlerRegistry = new HandlerRegistry();
        $this->objectConstructor = new UnserializeObjectConstructor();

        $this->metadataFactory = new MetadataFactory(new AnnotationLoader(new AnnotationReader()));
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
        return array(array(
            'type' => 'JsonSerializable',
            'format' => 'foo',
            'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
            'method' => 'serialize'
        ));
    }
}
