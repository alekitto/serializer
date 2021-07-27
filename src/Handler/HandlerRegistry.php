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
use function get_class;
use function interface_exists;
use function is_array;
use function is_callable;
use function Safe\preg_match;
use function Safe\sprintf;
use function Safe\substr;
use function strrpos;

final class HandlerRegistry implements HandlerRegistryInterface
{
    /** @var array<int, mixed> */
    private array $handlers;

    public static function getDefaultMethod(int $direction, string $type): string
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

        switch ($direction) {
            case Direction::DIRECTION_DESERIALIZATION:
                return 'deserialize' . $type;

            case Direction::DIRECTION_SERIALIZATION:
                return 'serialize' . $type;

            default:
                throw new LogicException(sprintf('The direction %d does not exist; see Direction constants.', $direction));
        }
    }

    /**
     * @param array<int, mixed> $handlers
     */
    public function __construct(array $handlers = [])
    {
        $this->handlers = $handlers;
    }

    public function registerSubscribingHandler(SubscribingHandlerInterface $handler): self
    {
        foreach ($handler->getSubscribingMethods() as $methodData) {
            if (! isset($methodData['type'])) {
                throw new RuntimeException(sprintf('For each subscribing method a "type" attribute must be given for %s.', get_class($handler)));
            }

            $directions = [Direction::DIRECTION_DESERIALIZATION, Direction::DIRECTION_SERIALIZATION];
            if (isset($methodData['direction'])) {
                $directions = [$methodData['direction']];
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

    /**
     * {@inheritdoc}
     */
    public function registerHandler(int $direction, string $typeName, $handler): HandlerRegistryInterface
    {
        $this->handlers[$direction][$typeName] = $handler;

        return $this;
    }

    public function registerSerializationHandler(SerializationHandlerInterface $handler): HandlerRegistryInterface
    {
        return $this->registerHandler(Direction::DIRECTION_SERIALIZATION, $handler::getType(), new InternalSerializationHandler([$handler, 'serialize']));
    }

    public function registerDeserializationHandler(DeserializationHandlerInterface $handler): HandlerRegistryInterface
    {
        return $this->registerHandler(Direction::DIRECTION_DESERIALIZATION, $handler::getType(), new InternalDeserializationHandler([$handler, 'deserialize']));
    }

    public function getHandler(int $direction, string $typeName): ?callable
    {
        if (! isset($this->handlers[$direction][$typeName])) {
            return null;
        }

        $v = &$this->handlers[$direction][$typeName];
        if (is_array($v) && isset($v[0]) && $v[0] instanceof Closure) {
            $v[0] = $v[0]();
        }

        return $v;
    }
}
