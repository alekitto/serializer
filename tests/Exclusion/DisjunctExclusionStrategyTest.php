<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Exclusion;

use Kcs\Serializer\Exclusion\DisjunctExclusionStrategy;
use Kcs\Serializer\Exclusion\ExclusionStrategyInterface;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\StaticPropertyMetadata;
use Kcs\Serializer\SerializationContext;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class DisjunctExclusionStrategyTest extends TestCase
{
    public function testShouldSkipClassShortCircuiting(): void
    {
        $metadata = new ClassMetadata(new \ReflectionClass(\stdClass::class));
        $context = SerializationContext::create();

        $first = $this->prophesize(ExclusionStrategyInterface::class);
        $last = $this->prophesize(ExclusionStrategyInterface::class);

        $strat = new DisjunctExclusionStrategy([
            $first->reveal(),
            $last->reveal(),
        ]);

        $first->shouldSkipClass($metadata, $context)->willReturn(true);
        $last->shouldSkipClass(Argument::cetera())->shouldNotBeCalled();

        self::assertTrue($strat->shouldSkipClass($metadata, $context));
    }

    public function testShouldSkipClassDisjunctBehavior(): void
    {
        $metadata = new ClassMetadata(new \ReflectionClass(\stdClass::class));
        $context = SerializationContext::create();

        $first = $this->prophesize(ExclusionStrategyInterface::class);
        $last = $this->prophesize(ExclusionStrategyInterface::class);

        $strat = new DisjunctExclusionStrategy([
            $first->reveal(),
            $last->reveal(),
        ]);

        $first->shouldSkipClass($metadata, $context)->willReturn(false);
        $last->shouldSkipClass($metadata, $context)->willReturn(true);

        self::assertTrue($strat->shouldSkipClass($metadata, $context));
    }

    public function testShouldSkipClassReturnsFalseIfNoPredicateMatched(): void
    {
        $metadata = new ClassMetadata(new \ReflectionClass(\stdClass::class));
        $context = SerializationContext::create();

        $first = $this->prophesize(ExclusionStrategyInterface::class);
        $last = $this->prophesize(ExclusionStrategyInterface::class);

        $strat = new DisjunctExclusionStrategy([
            $first->reveal(),
            $last->reveal(),
        ]);

        $first->shouldSkipClass($metadata, $context)->willReturn(false);
        $last->shouldSkipClass($metadata, $context)->willReturn(false);

        self::assertFalse($strat->shouldSkipClass($metadata, $context));
    }

    public function testShouldSkipPropertyShortCircuiting(): void
    {
        $metadata = new StaticPropertyMetadata(\stdClass::class, 'foo', 'bar');
        $context = SerializationContext::create();

        $first = $this->prophesize(ExclusionStrategyInterface::class);
        $last = $this->prophesize(ExclusionStrategyInterface::class);

        $strat = new DisjunctExclusionStrategy([
            $first->reveal(),
            $last->reveal(),
        ]);

        $first->shouldSkipProperty($metadata, $context)->willReturn(true);
        $last->shouldSkipProperty(Argument::cetera())->shouldNotBeCalled();

        self::assertTrue($strat->shouldSkipProperty($metadata, $context));
    }

    public function testShouldSkipPropertyDisjunct(): void
    {
        $metadata = new StaticPropertyMetadata(\stdClass::class, 'foo', 'bar');
        $context = SerializationContext::create();

        $first = $this->prophesize(ExclusionStrategyInterface::class);
        $last = $this->prophesize(ExclusionStrategyInterface::class);

        $strat = new DisjunctExclusionStrategy([
            $first->reveal(),
            $last->reveal(),
        ]);

        $first->shouldSkipProperty($metadata, $context)->willReturn(false);
        $last->shouldSkipProperty($metadata, $context)->willReturn(true);

        self::assertTrue($strat->shouldSkipProperty($metadata, $context));
    }

    public function testShouldSkipPropertyReturnsFalseIfNoPredicateMatches(): void
    {
        $metadata = new StaticPropertyMetadata(\stdClass::class, 'foo', 'bar');
        $context = SerializationContext::create();

        $first = $this->prophesize(ExclusionStrategyInterface::class);
        $last = $this->prophesize(ExclusionStrategyInterface::class);

        $strat = new DisjunctExclusionStrategy([
            $first->reveal(),
            $last->reveal(),
        ]);

        $first->shouldSkipProperty($metadata, $context)->willReturn(false);
        $last->shouldSkipProperty($metadata, $context)->willReturn(false);

        self::assertFalse($strat->shouldSkipProperty($metadata, $context));
    }
}
