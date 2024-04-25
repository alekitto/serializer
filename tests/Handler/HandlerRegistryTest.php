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
    public function testDefaultMethodNameShouldWork(string $type, Direction $direction, string $expected): void
    {
        self::assertEqualsIgnoringCase($expected, HandlerRegistry::getDefaultMethod($direction, $type));
    }

    public function provideDefaultMethod(): iterable
    {
        yield ['id', Direction::Serialization, 'serializeId'];
        yield ['id', Direction::Deserialization, 'deserializeId'];
        yield [__CLASS__, Direction::Serialization, 'serializeHandlerRegistryTest'];
        yield ['\stdClass', Direction::Serialization, 'serializestdClass'];
    }

    public function testDefaultMethodShouldThrowIfValidNameCannotBeGenerated(): void
    {
        $this->expectExceptionMessage('Cannot derive a valid method name for type "array<string>". Please define the method name manually');
        $this->expectException(LogicException::class);

        HandlerRegistry::getDefaultMethod(Direction::Serialization, 'array<string>');
    }
}
