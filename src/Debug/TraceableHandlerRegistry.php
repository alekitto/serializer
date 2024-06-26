<?php

declare(strict_types=1);

namespace Kcs\Serializer\Debug;

use Closure;
use Kcs\Serializer\Direction;
use Kcs\Serializer\Handler\DeserializationHandlerInterface;
use Kcs\Serializer\Handler\HandlerRegistryInterface;
use Kcs\Serializer\Handler\InternalDeserializationHandler;
use Kcs\Serializer\Handler\InternalSerializationHandler;
use Kcs\Serializer\Handler\SerializationHandlerInterface;
use Kcs\Serializer\Handler\SubscribingHandlerInterface;
use ProxyManager\Proxy\ProxyInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Throwable;

use function call_user_func_array;
use function class_exists;
use function func_get_args;
use function is_array;
use function is_callable;
use function is_object;

class TraceableHandlerRegistry implements HandlerRegistryInterface
{
    private VarCloner $cloner;

    /**
     * @var array<int, mixed>
     * @phpstan-var array{type: string, direction: string, handler: string, exception: Data|null}[]
     */
    public array $calls = [];

    public function __construct(private readonly HandlerRegistryInterface $decorated, VarCloner|null $cloner = null)
    {
        $this->cloner = $cloner ?? new VarCloner();
    }

    public function registerSubscribingHandler(SubscribingHandlerInterface $handler): self
    {
        $this->decorated->registerSubscribingHandler($handler);

        return $this;
    }

    public function registerHandler(Direction $direction, string $typeName, callable $handler): HandlerRegistryInterface
    {
        $this->decorated->registerHandler($direction, $typeName, $handler);

        return $this;
    }

    public function registerSerializationHandler(SerializationHandlerInterface $handler): HandlerRegistryInterface
    {
        $this->decorated->registerSerializationHandler($handler);

        return $this;
    }

    public function registerDeserializationHandler(DeserializationHandlerInterface $handler): HandlerRegistryInterface
    {
        $this->decorated->registerDeserializationHandler($handler);

        return $this;
    }

    public function getHandler(Direction $direction, string $typeName): callable|null
    {
        $callable = $this->decorated->getHandler($direction, $typeName);
        if ($callable === null) {
            return null;
        }

        return function () use ($typeName, $direction, $callable) {
            try {
                return call_user_func_array($callable, func_get_args());
            } catch (Throwable $e) {
                throw $e;
            } finally {
                $this->calls[] = [
                    'type' => $typeName,
                    'direction' => $direction === Direction::Serialization ? 'SERIALIZE' : 'DESERIALIZE',
                    'handler' => $this->getCallableName($callable),
                    'exception' => isset($e) ? $this->cloner->cloneVar($e) : null,
                ];
            }
        };
    }

    public function reset(): void
    {
        $this->calls = [];
    }

    private function getCallableName(callable $callable): string
    {
        if ($callable instanceof InternalSerializationHandler) {
            // @phpstan-ignore-next-line
            $callable = (fn () => $this->handler)->bindTo($callable, InternalSerializationHandler::class)();
        }

        if ($callable instanceof InternalDeserializationHandler) {
            // @phpstan-ignore-next-line
            $callable = (fn () => $this->handler)->bindTo($callable, InternalDeserializationHandler::class)();
        }

        $methodName = null;
        if (is_array($callable)) {
            $reflClass = new ReflectionClass($callable[0]);
            $r = new ReflectionMethod($callable[0], $callable[1]);
            $className = class_exists(ProxyInterface::class) &&
                $reflClass->isSubclassOf(ProxyInterface::class) &&
                ($parent = $reflClass->getParentClass()) ? $parent->getName() : $reflClass->getName();

            $methodName = $className . '::' . $r->getName();
        } elseif (is_object($callable) && is_callable([$callable, '__invoke'])) {
            $reflClass = new ReflectionClass($callable);
            $methodName = class_exists(ProxyInterface::class) &&
                $reflClass->isSubclassOf(ProxyInterface::class) &&
                ($parent = $reflClass->getParentClass()) ? $parent->getShortName() : $reflClass->getShortName();
        } else {
            $r = new ReflectionFunction(Closure::fromCallable($callable));
            $methodName = $r->getName();
        }

        return $methodName;
    }
}
