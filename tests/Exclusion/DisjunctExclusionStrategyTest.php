<?php declare(strict_types=1);
/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 * Copyright 2017 Alessandro Chitolina <alekitto@gmail.com>
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

namespace Kcs\Serializer\Tests\Exclusion;

use Kcs\Serializer\Exclusion\DisjunctExclusionStrategy;
use Kcs\Serializer\Exclusion\ExclusionStrategyInterface;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\StaticPropertyMetadata;
use Kcs\Serializer\SerializationContext;
use PHPUnit\Framework\TestCase;

class DisjunctExclusionStrategyTest extends TestCase
{
    public function testShouldSkipClassShortCircuiting()
    {
        $metadata = new ClassMetadata(new \ReflectionClass(\stdClass::class));
        $context = SerializationContext::create();

        $strat = new DisjunctExclusionStrategy([
            $first = $this->createMock(ExclusionStrategyInterface::class),
            $last = $this->createMock(ExclusionStrategyInterface::class),
        ]);

        $first->expects($this->once())
            ->method('shouldSkipClass')
            ->with($metadata, $context)
            ->will($this->returnValue(true));

        $last->expects($this->never())
            ->method('shouldSkipClass');

        $this->assertTrue($strat->shouldSkipClass($metadata, $context));
    }

    public function testShouldSkipClassDisjunctBehavior()
    {
        $metadata = new ClassMetadata(new \ReflectionClass(\stdClass::class));
        $context = SerializationContext::create();

        $strat = new DisjunctExclusionStrategy([
            $first = $this->createMock(ExclusionStrategyInterface::class),
            $last = $this->createMock(ExclusionStrategyInterface::class),
        ]);

        $first->expects($this->once())
            ->method('shouldSkipClass')
            ->with($metadata, $context)
            ->will($this->returnValue(false));

        $last->expects($this->once())
            ->method('shouldSkipClass')
            ->with($metadata, $context)
            ->will($this->returnValue(true));

        $this->assertTrue($strat->shouldSkipClass($metadata, $context));
    }

    public function testShouldSkipClassReturnsFalseIfNoPredicateMatched()
    {
        $metadata = new ClassMetadata(new \ReflectionClass(\stdClass::class));
        $context = SerializationContext::create();

        $strat = new DisjunctExclusionStrategy([
            $first = $this->createMock(ExclusionStrategyInterface::class),
            $last = $this->createMock(ExclusionStrategyInterface::class),
        ]);

        $first->expects($this->once())
            ->method('shouldSkipClass')
            ->with($metadata, $context)
            ->will($this->returnValue(false));

        $last->expects($this->once())
            ->method('shouldSkipClass')
            ->with($metadata, $context)
            ->will($this->returnValue(false));

        $this->assertFalse($strat->shouldSkipClass($metadata, $context));
    }

    public function testShouldSkipPropertyShortCircuiting()
    {
        $metadata = new StaticPropertyMetadata(\stdClass::class, 'foo', 'bar');
        $context = SerializationContext::create();

        $strat = new DisjunctExclusionStrategy([
            $first = $this->createMock(ExclusionStrategyInterface::class),
            $last = $this->createMock(ExclusionStrategyInterface::class),
        ]);

        $first->expects($this->once())
            ->method('shouldSkipProperty')
            ->with($metadata, $context)
            ->will($this->returnValue(true));

        $last->expects($this->never())
            ->method('shouldSkipProperty');

        $this->assertTrue($strat->shouldSkipProperty($metadata, $context));
    }

    public function testShouldSkipPropertyDisjunct()
    {
        $metadata = new StaticPropertyMetadata(\stdClass::class, 'foo', 'bar');
        $context = SerializationContext::create();

        $strat = new DisjunctExclusionStrategy([
            $first = $this->createMock(ExclusionStrategyInterface::class),
            $last = $this->createMock(ExclusionStrategyInterface::class),
        ]);

        $first->expects($this->once())
            ->method('shouldSkipProperty')
            ->with($metadata, $context)
            ->will($this->returnValue(false));

        $last->expects($this->once())
            ->method('shouldSkipProperty')
            ->with($metadata, $context)
            ->will($this->returnValue(true));

        $this->assertTrue($strat->shouldSkipProperty($metadata, $context));
    }

    public function testShouldSkipPropertyReturnsFalseIfNoPredicateMatches()
    {
        $metadata = new StaticPropertyMetadata(\stdClass::class, 'foo', 'bar');
        $context = SerializationContext::create();

        $strat = new DisjunctExclusionStrategy([
            $first = $this->createMock(ExclusionStrategyInterface::class),
            $last = $this->createMock(ExclusionStrategyInterface::class),
        ]);

        $first->expects($this->once())
            ->method('shouldSkipProperty')
            ->with($metadata, $context)
            ->will($this->returnValue(false));

        $last->expects($this->once())
            ->method('shouldSkipProperty')
            ->with($metadata, $context)
            ->will($this->returnValue(false));

        $this->assertFalse($strat->shouldSkipProperty($metadata, $context));
    }
}
