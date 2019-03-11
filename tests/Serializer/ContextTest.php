<?php declare(strict_types=1);

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
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ContextTest extends TestCase
{
    public function testSerializationContextPathAndDepth(): void
    {
        $object = new Node([
            new Node(),
            new Node([
                new Node(),
            ]),
        ]);
        $objects = [$object, $object->children[0], $object->children[1], $object->children[1]->children[0]];

        $navigator = $this->prophesize(GraphNavigator::class);

        $context = new SerializationContext();
        $context->initialize(
            'json',
            $this->prophesize(VisitorInterface::class)->reveal(),
            $navigator->reveal(),
            $this->prophesize(MetadataFactoryInterface::class)->reveal()
        );

        $context->startVisiting($objects[0]);
        self::assertEquals(1, $context->getDepth());
        $context->startVisiting($objects[1]);
        self::assertEquals(2, $context->getDepth());
        $context->startVisiting($objects[2]);
        self::assertEquals(3, $context->getDepth());
    }

    public function testSerializationMetadataStack(): void
    {
        $object = new Node([
            $child = new InlineChild(),
        ]);

        $exclusionStrategy = $this->prophesize(ExclusionStrategyInterface::class);
        $exclusionStrategy->shouldSkipClass(Argument::any(), Argument::any())->willReturn(false);
        $exclusionStrategy->shouldSkipProperty(Argument::type(PropertyMetadata::class), Argument::type(SerializationContext::class))
            ->will(function ($args) {
                /** @var SerializationContext $context */
                [$propertyMetadata, $context] = $args;
                $stack = $context->getMetadataStack();

                if (Node::class === $propertyMetadata->class && 'children' === $propertyMetadata->name) {
                    Assert::assertEquals(0, $stack->count());
                }

                if (InlineChild::class === $propertyMetadata->class) {
                    Assert::assertEquals(1, $stack->count());
                    Assert::assertEquals('children', $stack->getCurrent()->getName());
                }

                return false;
            });

        $serializer = SerializerBuilder::create()->build();
        $serializer->serialize($object, 'json', SerializationContext::create()->addExclusionStrategy($exclusionStrategy->reveal()));
    }

    public function testSerializeNullOption(): void
    {
        $context = SerializationContext::create();
        self::assertFalse($context->shouldSerializeNull());

        $context->setSerializeNull(false);
        self::assertFalse($context->shouldSerializeNull());

        $context->setSerializeNull(true);
        self::assertTrue($context->shouldSerializeNull());

        $context->setSerializeNull('foo');
        self::assertTrue($context->shouldSerializeNull());

        $context->setSerializeNull('0');
        self::assertFalse($context->shouldSerializeNull());
    }

    public function testContextShouldBeCloneable(): void
    {
        $context = SerializationContext::create();
        $context->setGroups(['foobar']);

        $ctx2 = clone $context;
        $ctx2->setGroups(['bar', 'foobar']);

        self::assertEquals(['foobar'], $context->attributes->get('groups'));
        self::assertEquals(['bar', 'foobar'], $ctx2->attributes->get('groups'));
    }
}
