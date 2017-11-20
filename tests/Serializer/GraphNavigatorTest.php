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
use Kcs\Serializer\Metadata\MetadataStack;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class GraphNavigatorTest extends \PHPUnit_Framework_TestCase
{
    private $metadataFactory;
    private $handlerRegistry;
    private $objectConstructor;
    private $dispatcher;
    private $navigator;
    private $additionalFieldRegistry;

    /**
     * @expectedException \Kcs\Serializer\Exception\RuntimeException
     * @expectedExceptionMessage Resources are not supported in serialized data.
     */
    public function testResourceThrowsException()
    {
        $context = $this->createMock(SerializationContext::class);

        $context->expects($this->any())
            ->method('getVisitor')
            ->will($this->returnValue($this->createMock(VisitorInterface::class)));
        $context->expects($this->any())
            ->method('getDirection')
            ->will($this->returnValue(Direction::DIRECTION_SERIALIZATION));

        $this->navigator->accept(STDIN, null, $context);
    }

    public function testNavigatorPassesInstanceOnSerialization()
    {
        $metadataStack = $this->createMock(MetadataStack::class);
        $context = $this->createMock(SerializationContext::class);
        $context->method('getMetadataStack')->willReturn($metadataStack);

        $object = new SerializableClass();
        $metadata = $this->metadataFactory->getMetadataFor(get_class($object));

        $context->expects($this->any())
            ->method('getDirection')
            ->will($this->returnValue(Direction::DIRECTION_SERIALIZATION));

        $visitor = $this->createMock(VisitorInterface::class);
        $visitor->expects($this->any())
            ->method('visitObject')
            ->will($this->returnCallback(function (ClassMetadata $passedMetadata, $data, Type $type, Context $passedContext, ObjectConstructorInterface $objectConstructor) use ($context, $metadata) {
                $this->assertSame($metadata, $passedMetadata);
                $this->assertTrue($context === $passedContext);
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

        $metadataStack = $this->createMock(MetadataStack::class);
        $context = $this->createMock(SerializationContext::class);
        $context->method('getMetadataStack')->willReturn($metadataStack);
        $context->expects($this->any())
            ->method('getDirection')
            ->will($this->returnValue(Direction::DIRECTION_SERIALIZATION));

        $context->expects($this->any())
            ->method('getVisitor')
            ->will($this->returnValue($this->createMock(VisitorInterface::class)));

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
