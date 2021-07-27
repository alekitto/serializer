<?php

declare(strict_types=1);

namespace Kcs\Serializer\Tests\Handler;

use Kcs\Serializer\Direction;
use Kcs\Serializer\Exception\LogicException;
use Kcs\Serializer\Handler\HandlerRegistry;
use PHPUnit\Framework\TestCase;

class HandlerRegistryTest extends TestCase
{
    /**
     * @dataProvider provideDefaultMethod
     */
    public function testDefaultMethodNameShouldWork(string $type, int $direction, string $expected): void
    {
        self::assertEqualsIgnoringCase($expected, HandlerRegistry::getDefaultMethod($direction, $type));
    }

    public function provideDefaultMethod(): iterable
    {
        yield ['id', Direction::DIRECTION_SERIALIZATION, 'serializeId'];
        yield ['id', Direction::DIRECTION_DESERIALIZATION, 'deserializeId'];
        yield [__CLASS__, Direction::DIRECTION_SERIALIZATION, 'serializeHandlerRegistryTest'];
        yield ['\stdClass', Direction::DIRECTION_SERIALIZATION, 'serializestdClass'];
    }

    public function testDefaultMethodShouldThrowIfValidNameCannotBeGenerated(): void
    {
        $this->expectExceptionMessage('Cannot derive a valid method name for type "array<string>". Please define the method name manually');
        $this->expectException(LogicException::class);

        HandlerRegistry::getDefaultMethod(Direction::DIRECTION_SERIALIZATION, 'array<string>');
    }

    public function testDefaultMethodShouldThrowOnUnknownDirection(): void
    {
        $this->expectExceptionMessage('The direction -1 does not exist; see Direction constants.');
        $this->expectException(LogicException::class);

        HandlerRegistry::getDefaultMethod(-1, 'id');
    }
}
