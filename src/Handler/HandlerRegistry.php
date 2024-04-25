<?php

declare(strict_types=1);

namespace Kcs\Serializer\Handler;

use Closure;
use Kcs\Serializer\Direction;
use Kcs\Serializer\Exception\LogicException;
use Kcs\Serializer\Exception\RuntimeException;
use ReflectionClass;

use function assert;
use function class_exists;
use function interface_exists;
use function is_array;
use function is_callable;
use function is_string;
use function Safe\preg_match;
use function sprintf;
use function strrpos;
use function substr;

final class HandlerRegistry implements HandlerRegistryInterface
{
    public static function getDefaultMethod(Direction $direction, string $type): string
    {
        $pos = strrpos($type, '\\');
        if ($pos !== false) {
            $type = substr($type, $pos + 1);
        }

        if (class_exists($type) || interface_exists($type)) {
            $type = (new ReflectionClass($type))->getShortName();
        }

        if (! preg_match('/^[a-zA-Z0-9_\x80-\xff]*$/', $type)) {
            throw new LogicException(sprintf('Cannot derive a valid method name for type "%s". Please define the method name manually', $type));
        }

        return match ($direction) {
            Direction::Deserialization => 'deserialize' . $type,
            Direction::Serialization => 'serialize' . $type,
        };
    }

    /** @param array<string, mixed> $handlers */
    public function __construct(private array $handlers = [])
    {
    }

    public function registerSubscribingHandler(SubscribingHandlerInterface $handler): self
    {
        foreach ($handler->getSubscribingMethods() as $methodData) {
            if (! isset($methodData['type'])) {
                throw new RuntimeException(sprintf('For each subscribing method a "type" attribute must be given for %s.', $handler::class));
            }

            $directions = [Direction::Deserialization, Direction::Serialization];
            if (isset($methodData['direction'])) {
                $direction = $methodData['direction'];
                if (is_string($direction)) {
                    $direction = Direction::parseDirection($direction);
                }

                $directions = [$direction];
            }

            foreach ($directions as $direction) {
                $method = $methodData['method'] ?? self::getDefaultMethod($direction, $methodData['type']);
                $inner = [$handler, $method];
                assert(is_callable($inner));

                $this->registerHandler($direction, $methodData['type'], $inner);
            }
        }

        return $this;
    }

    public function registerHandler(Direction $direction, string $typeName, callable $handler): HandlerRegistryInterface
    {
        $this->handlers[$direction->name][$typeName] = $handler;

        return $this;
    }

    public function registerSerializationHandler(SerializationHandlerInterface $handler): HandlerRegistryInterface
    {
        return $this->registerHandler(Direction::Serialization, $handler::getType(), new InternalSerializationHandler([$handler, 'serialize']));
    }

    public function registerDeserializationHandler(DeserializationHandlerInterface $handler): HandlerRegistryInterface
    {
        return $this->registerHandler(Direction::Deserialization, $handler::getType(), new InternalDeserializationHandler([$handler, 'deserialize']));
    }

    public function getHandler(Direction $direction, string $typeName): callable|null
    {
        if (! isset($this->handlers[$direction->name][$typeName])) {
            return null;
        }

        $v = &$this->handlers[$direction->name][$typeName];
        if (is_array($v) && isset($v[0]) && $v[0] instanceof Closure) {
            $v[0] = $v[0]();
        }

        return $v;
    }
}
