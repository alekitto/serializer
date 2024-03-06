<?php

declare(strict_types=1);

namespace Kcs\Serializer\Tests\Debug;

use Kcs\Metadata\Factory\MetadataFactoryInterface;
use Kcs\Serializer\Context;
use Kcs\Serializer\Debug\TraceableVisitor;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\SerializeGraphNavigator;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\Test\TestLogger;
use ReflectionClass;

class TraceableVisitorTest extends TestCase
{
    use ProphecyTrait;

    /** @var VisitorInterface|ObjectProphecy */
    private ObjectProphecy $innerVisitor;

    /** @var SerializeGraphNavigator|ObjectProphecy */
    private ObjectProphecy $navigator;

    private TestLogger $logger;
    private TraceableVisitor $visitor;
    private Context $context;

    protected function setUp(): void
    {
        $this->logger = new TestLogger();
        $this->innerVisitor = $this->prophesize(VisitorInterface::class);
        $this->visitor = new TraceableVisitor($this->innerVisitor->reveal(), $this->logger);
        $this->navigator = $this->prophesize(SerializeGraphNavigator::class);
        $this->context = SerializationContext::create();
        $this->context->initialize('json', $this->visitor, $this->navigator->reveal(), $this->prophesize(MetadataFactoryInterface::class)->reveal());
    }

    public function testVisitNull(): void
    {
        $this->innerVisitor
            ->visitNull(null, Type::null(), $this->context)
            ->willReturn(null)
            ->shouldBeCalledOnce();
        $this->visitor->visitNull(null, Type::null(), $this->context);

        self::assertEquals([
            [
                'level' => 'debug',
                'message' => 'Visiting null at path {path}',
                'context' => [
                    'path' => '<root>',
                    'type' => [
                        'name' => 'NULL',
                        'params' => [],
                    ],
                ],
            ],
        ], $this->logger->records);
    }

    public function testVisitString(): void
    {
        $this->innerVisitor
            ->visitString('foobar', Type::from('string'), $this->context)
            ->willReturn('foobar')
            ->shouldBeCalledOnce();
        $this->visitor->visitString('foobar', Type::from('string'), $this->context);

        self::assertEquals([
            [
                'level' => 'debug',
                'message' => 'Visiting string at path {path}',
                'context' => [
                    'path' => '<root>',
                    'data' => 'foobar',
                    'type' => [
                        'name' => 'string',
                        'params' => [],
                    ],
                ],
            ],
        ], $this->logger->records);
    }

    public function testVisitBoolean(): void
    {
        $this->innerVisitor
            ->visitBoolean(true, Type::from('bool'), $this->context)
            ->willReturn('true')
            ->shouldBeCalledOnce();
        $this->visitor->visitBoolean(true, Type::from('bool'), $this->context);

        self::assertEquals([
            [
                'level' => 'debug',
                'message' => 'Visiting boolean at path {path}',
                'context' => [
                    'path' => '<root>',
                    'data' => true,
                    'type' => [
                        'name' => 'bool',
                        'params' => [],
                    ],
                ],
            ],
        ], $this->logger->records);
    }

    public function testVisitDouble(): void
    {
        $this->innerVisitor
            ->visitDouble(.3, Type::from('float'), $this->context)
            ->willReturn('0.3')
            ->shouldBeCalledOnce();
        $this->visitor->visitDouble(.3, Type::from('float'), $this->context);

        self::assertEquals([
            [
                'level' => 'debug',
                'message' => 'Visiting float/double at path {path}',
                'context' => [
                    'path' => '<root>',
                    'data' => .3,
                    'type' => [
                        'name' => 'float',
                        'params' => [],
                    ],
                ],
            ],
        ], $this->logger->records);
    }

    public function testVisitInteger(): void
    {
        $this->innerVisitor
            ->visitInteger(42, Type::from('int'), $this->context)
            ->willReturn('42')
            ->shouldBeCalledOnce();
        $this->visitor->visitInteger(42, Type::from('int'), $this->context);

        self::assertEquals([
            [
                'level' => 'debug',
                'message' => 'Visiting integer at path {path}',
                'context' => [
                    'path' => '<root>',
                    'data' => 42,
                    'type' => [
                        'name' => 'int',
                        'params' => [],
                    ],
                ],
            ],
        ], $this->logger->records);
    }

    public function testVisitArray(): void
    {
        $this->innerVisitor
            ->visitArray(['foo', 'bar', 'foobar'], Type::parse('array<string>'), $this->context)
            ->willReturn('[foo,bar,foobar]')
            ->shouldBeCalledOnce();
        $this->visitor->visitArray(['foo', 'bar', 'foobar'], Type::parse('array<string>'), $this->context);

        self::assertEquals([
            [
                'level' => 'debug',
                'message' => 'Visiting array at path {path}',
                'context' => [
                    'path' => '<root>',
                    'data' => ['foo', 'bar', 'foobar'],
                    'type' => [
                        'name' => 'array',
                        'params' => [
                            ['name' => 'string', 'params' => []],
                        ],
                    ],
                ],
            ],
        ], $this->logger->records);
    }

    public function testVisitHash(): void
    {
        $this->innerVisitor
            ->visitHash(['foo' => 'bar', 'foobar' => 'bazbaz'], Type::parse('array<string, string>'), $this->context)
            ->willReturn('{"foo":"bar","foobar":"bazbaz"}')
            ->shouldBeCalledOnce();
        $this->visitor->visitHash(['foo' => 'bar', 'foobar' => 'bazbaz'], Type::parse('array<string, string>'), $this->context);

        self::assertEquals([
            [
                'level' => 'debug',
                'message' => 'Visiting hashmap at path {path}',
                'context' => [
                    'path' => '<root>',
                    'data' => ['foo' => 'bar', 'foobar' => 'bazbaz'],
                    'type' => [
                        'name' => 'array',
                        'params' => [
                            ['name' => 'string', 'params' => []],
                            ['name' => 'string', 'params' => []],
                        ],
                    ],
                ],
            ],
        ], $this->logger->records);
    }

    public function testVisitObject(): void
    {
        $metadata = new ClassMetadata(new ReflectionClass($this));

        $this->innerVisitor
            ->visitObject($metadata, $this, Type::from($this), $this->context, null)
            ->willReturn('{}')
            ->shouldBeCalledOnce();
        $this->visitor->visitObject($metadata, $this, Type::from($this), $this->context, null);

        self::assertEquals([
            [
                'level' => 'debug',
                'message' => 'Start visiting object at path {path}',
                'context' => [
                    'path' => '<root>',
                    'data' => $this,
                    'type' => [
                        'name' => self::class,
                        'params' => [],
                    ],
                ],
            ],
        ], $this->logger->records);
    }

    /**
     * @dataProvider provideCustomArgs
     */
    public function testVisitCustom(string $handlerString, callable $handler): void
    {
        $this->innerVisitor
            ->visitCustom($handler, $this, Type::from($this), $this->context)
            ->willReturn('custom')
            ->shouldBeCalledOnce();
        $this->visitor->visitCustom($handler, $this, Type::from($this), $this->context);

        self::assertEquals([
            [
                'level' => 'debug',
                'message' => 'Calling custom handler "{handler}" at path {path}',
                'context' => [
                    'path' => '<root>',
                    'handler' => $handlerString,
                    'data' => $this,
                    'type' => [
                        'name' => self::class,
                        'params' => [],
                    ],
                ],
            ],
        ], $this->logger->records);
    }

    public function provideCustomArgs(): iterable
    {
        yield [self::class . '::testVisitCustom', [$this, 'testVisitCustom']];
        yield [TestObject::class . '::staticFn', [TestObject::class, 'staticFn']];
        yield [TestObject::class . '::staticFn', TestObject::class . '::staticFn'];
        yield ['Closure (file: ' . __FILE__ . ', line: ' . __LINE__ . ')', static fn () => null];
    }
}

class TestObject
{
    public static function staticFn()
    {
    }
}
