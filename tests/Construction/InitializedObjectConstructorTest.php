<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Construction;

use Kcs\Serializer\Construction\InitializedObjectConstructor;
use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\DeserializationContext;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use stdClass;

class InitializedObjectConstructorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectConstructorInterface|ObjectProphecy
     */
    private ObjectProphecy $fallback;
    private InitializedObjectConstructor $constructor;

    protected function setUp(): void
    {
        $this->fallback = $this->prophesize(ObjectConstructorInterface::class);
        $this->constructor = new InitializedObjectConstructor($this->fallback->reveal());
    }

    public function testShouldCallFallbackConstructor(): void
    {
        $visitor = $this->prophesize(VisitorInterface::class);
        $metadata = $this->prophesize(ClassMetadata::class);
        $data = new stdClass();
        $context = DeserializationContext::create();
        $context->increaseDepth();

        $this->fallback->construct($visitor, $metadata, $data, Type::from($data), $context)
            ->shouldBeCalled()
            ->willReturn(new stdClass());

        $this->constructor->construct($visitor->reveal(), $metadata->reveal(), $data, Type::from($data), $context);
    }

    public function testShouldReturnTargetIfSet(): void
    {
        $visitor = $this->prophesize(VisitorInterface::class);
        $metadata = $this->prophesize(ClassMetadata::class);
        $data = new stdClass();
        $context = DeserializationContext::create();
        $context->increaseDepth();
        $context->setAttribute('target', $this);

        $this->fallback->construct($visitor, $metadata, $data, Type::from($data), $context)
            ->shouldNotBeCalled();

        self::assertSame($this, $this->constructor->construct($visitor->reveal(), $metadata->reveal(), $data, Type::from($data), $context));
    }
}
