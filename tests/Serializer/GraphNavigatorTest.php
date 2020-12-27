<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Serializer;

use Doctrine\Common\Annotations\AnnotationReader;
use Kcs\Metadata\Factory\MetadataFactoryInterface;
use Kcs\Serializer\Direction;
use Kcs\Serializer\EventDispatcher\PreSerializeEvent;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Exclusion\ExclusionStrategyInterface;
use Kcs\Serializer\GraphNavigator;
use Kcs\Serializer\Handler\HandlerRegistry;
use Kcs\Serializer\Handler\HandlerRegistryInterface;
use Kcs\Serializer\Handler\SubscribingHandlerInterface;
use Kcs\Serializer\Metadata\Loader\AnnotationLoader;
use Kcs\Serializer\Metadata\MetadataFactory;
use Kcs\Serializer\Metadata\MetadataStack;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\SerializeGraphNavigator;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class GraphNavigatorTest extends TestCase
{
    use ProphecyTrait;

    private MetadataFactoryInterface $metadataFactory;
    private HandlerRegistryInterface $handlerRegistry;
    private EventDispatcherInterface $dispatcher;
    private GraphNavigator $navigator;

    public function testResourceThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Resources are not supported in serialized data.');

        $context = $this->prophesize(SerializationContext::class);
        $context->visitor = $this->prophesize(VisitorInterface::class);
        $context->direction = Direction::DIRECTION_SERIALIZATION;
        $context->guessType(STDIN)->willReturn(new Type('resource'));

        $this->navigator->accept(STDIN, null, $context->reveal());
    }

    public function testNavigatorPassesInstanceOnSerialization(): void
    {
        $context = $this->prophesize(SerializationContext::class);
        $context->getMetadataStack()->willReturn($this->prophesize(MetadataStack::class));

        $object = new SerializableClass();
        $metadata = $this->metadataFactory->getMetadataFor(\get_class($object));

        $context->direction = Direction::DIRECTION_SERIALIZATION;
        $context->visitor = $visitor = $this->prophesize(VisitorInterface::class);
        $visitor->visitObject(Argument::cetera())->willReturn();
        $context->guessType($object)->willReturn(new Type(SerializableClass::class));

        $visitor->startVisiting(Argument::cetera())->shouldBeCalled();
        $visitor->endVisiting(Argument::cetera())->willReturn();

        $context->isVisiting(Argument::any())->willReturn(false);
        $context->addMethodProphecy($context->startVisiting(Argument::any()));
        $context->addMethodProphecy($context->stopVisiting(Argument::any()));

        $exclusionStrategy = $this->prophesize(ExclusionStrategyInterface::class);
        $exclusionStrategy->shouldSkipClass($metadata, $context)->willReturn(false);
        $context->getExclusionStrategy()->willReturn($exclusionStrategy);

        $this->navigator = new SerializeGraphNavigator($this->metadataFactory, $this->handlerRegistry, $this->dispatcher);
        $this->navigator->accept($object, null, $context->reveal());

        $visitor->visitObject($metadata, Argument::any(), Argument::type(Type::class), $context)
            ->shouldHaveBeenCalled()
        ;
    }

    public function testNavigatorChangeTypeOnSerialization(): void
    {
        $object = new SerializableClass();

        $this->dispatcher->addListener(PreSerializeEvent::class, static function (PreSerializeEvent $event) {
            $type = $event->getType();
            $type->name = \JsonSerializable::class;
        });

        $this->handlerRegistry->registerSubscribingHandler($handler = new TestSubscribingHandler());

        $context = $this->prophesize(SerializationContext::class);
        $context->getMetadataStack()->willReturn($this->prophesize(MetadataStack::class));
        $context->direction = Direction::DIRECTION_SERIALIZATION;
        $context->guessType($object)->willReturn(new Type(SerializableClass::class));

        $context->visitor = $visitor = $this->prophesize(VisitorInterface::class);
        $visitor->visitCustom(Argument::cetera())->willReturn();

        $visitor->startVisiting(Argument::cetera())->shouldBeCalled();
        $visitor->endVisiting(Argument::cetera())->willReturn();

        $context->isVisiting(Argument::any())->willReturn(false);
        $context->startVisiting(Argument::any())->shouldBeCalled();
        $context->stopVisiting(Argument::any())->shouldBeCalled();

        $this->navigator = new SerializeGraphNavigator($this->metadataFactory, $this->handlerRegistry, $this->dispatcher);
        $this->navigator->accept($object, null, $context->reveal());

        $visitor->visitCustom([$handler, 'serialize'], Argument::any(), Argument::type(Type::class), $context)
            ->shouldHaveBeenCalled();
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
        $this->handlerRegistry = new HandlerRegistry();

        $loader = new AnnotationLoader();
        $loader->setReader(new AnnotationReader());
        $this->metadataFactory = new MetadataFactory($loader);
        $this->navigator = new SerializeGraphNavigator($this->metadataFactory, $this->handlerRegistry, $this->dispatcher);
    }
}

class SerializableClass
{
    /**
     * @var string
     */
    public $foo = 'bar';
}

class TestSubscribingHandler implements SubscribingHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribingMethods(): iterable
    {
        return [[
            'type' => \JsonSerializable::class,
            'direction' => Direction::DIRECTION_SERIALIZATION,
            'method' => 'serialize',
        ]];
    }

    public function serialize(): string
    {
        return '';
    }
}
