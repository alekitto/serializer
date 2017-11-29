<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Serializer;

use Doctrine\Common\Annotations\AnnotationReader;
use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Construction\UnserializeObjectConstructor;
use Kcs\Serializer\Direction;
use Kcs\Serializer\EventDispatcher\Events;
use Kcs\Serializer\EventDispatcher\PreSerializeEvent;
use Kcs\Serializer\Exclusion\ExclusionStrategyInterface;
use Kcs\Serializer\GraphNavigator;
use Kcs\Serializer\Handler\HandlerRegistry;
use Kcs\Serializer\Handler\SubscribingHandlerInterface;
use Kcs\Serializer\Metadata\Loader\AnnotationLoader;
use Kcs\Serializer\Metadata\MetadataFactory;
use Kcs\Serializer\Metadata\MetadataStack;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcher;

class GraphNavigatorTest extends TestCase
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
        $context = $this->prophesize(SerializationContext::class);
        $context->getVisitor()->willReturn($this->prophesize(VisitorInterface::class));
        $context->getDirection()->willReturn(Direction::DIRECTION_SERIALIZATION);

        $this->navigator->accept(STDIN, null, $context->reveal());
    }

    public function testNavigatorPassesInstanceOnSerialization()
    {
        $context = $this->prophesize(SerializationContext::class);
        $context->getMetadataStack()->willReturn($this->prophesize(MetadataStack::class));

        $object = new SerializableClass();
        $metadata = $this->metadataFactory->getMetadataFor(get_class($object));

        $context->getDirection()->willReturn(Direction::DIRECTION_SERIALIZATION);
        $context->getVisitor()->willReturn($visitor = $this->prophesize(VisitorInterface::class));
        $visitor->visitObject(Argument::cetera())->willReturn();

        $visitor->startVisiting(Argument::cetera())->willReturn();
        $visitor->endVisiting(Argument::cetera())->willReturn();

        $context->isVisiting(Argument::any())->willReturn(false);
        $context->startVisiting(Argument::any())->willReturn();
        $context->stopVisiting(Argument::any())->willReturn();
        $context->getExclusionStrategy()->willReturn($this->prophesize(ExclusionStrategyInterface::class));

        $this->navigator = new GraphNavigator($this->metadataFactory, $this->handlerRegistry, $this->objectConstructor, $this->dispatcher);
        $this->navigator->accept($object, null, $context->reveal());

        $visitor->visitObject($metadata, Argument::any(), Argument::type(Type::class), $context, Argument::type(ObjectConstructorInterface::class))
            ->shouldHaveBeenCalled();
    }

    public function testNavigatorChangeTypeOnSerialization()
    {
        $object = new SerializableClass();

        $this->dispatcher->addListener(Events::PRE_SERIALIZE, function (PreSerializeEvent $event) {
            $type = $event->getType();
            $type->setName(\JsonSerializable::class);
        });

        $this->handlerRegistry->registerSubscribingHandler($handler = new TestSubscribingHandler());

        $context = $this->prophesize(SerializationContext::class);
        $context->getMetadataStack()->willReturn($this->prophesize(MetadataStack::class));
        $context->getDirection()->willReturn(Direction::DIRECTION_SERIALIZATION);

        $context->getVisitor()->willReturn($visitor = $this->prophesize(VisitorInterface::class));
        $visitor->visitCustom(Argument::cetera())->willReturn();

        $visitor->startVisiting(Argument::cetera())->willReturn();
        $visitor->endVisiting(Argument::cetera())->willReturn();

        $context->isVisiting(Argument::any())->willReturn(false);
        $context->startVisiting(Argument::any())->willReturn();
        $context->stopVisiting(Argument::any())->willReturn();

        $this->navigator = new GraphNavigator($this->metadataFactory, $this->handlerRegistry, $this->objectConstructor, $this->dispatcher);
        $this->navigator->accept($object, null, $context->reveal());

        $visitor->visitCustom([$handler, 'serialize'], Argument::any(), Argument::type(Type::class), $context)
            ->shouldHaveBeenCalled();
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
            'type' => \JsonSerializable::class,
            'direction' => Direction::DIRECTION_SERIALIZATION,
            'method' => 'serialize',
        ]];
    }

    public function serialize()
    {
    }
}
