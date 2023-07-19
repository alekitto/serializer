<?php

declare(strict_types=1);

namespace Kcs\Serializer\Handler;

use Closure;
use Kcs\Serializer\Context;
use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;

use function assert;
use function get_debug_type;
use function is_array;
use function is_callable;
use function sprintf;

/** @internal */
final class InternalDeserializationHandler
{
    /** @var callable */
    private $handler;

    /**
     * @param mixed[] | callable $handler
     * @phpstan-param array{0: callable, 1: string} | callable $handler
     */
    public function __construct(array|callable $handler)
    {
        if (is_array($handler) && is_callable($handler[0])) {
            $handler[0] = $handler[0]();
        }

        assert(is_callable($handler));
        $this->handler = $handler;
    }

    public function __invoke(VisitorInterface $visitor, mixed $data, Type $type, Context $context): mixed
    {
        if (is_array($this->handler) && $this->handler[0] instanceof Closure) {
            $this->handler[0] = $this->handler[0]();
        }

        if (! is_callable($this->handler)) {
            throw new InvalidArgumentException(sprintf('Invalid deserialization handler: callable expected, %s passed', get_debug_type($this->handler)));
        }

        return ($this->handler)($data);
    }
}
