<?php

declare(strict_types=1);

namespace Kcs\Serializer\Handler;

use Closure;
use Kcs\Serializer\Context;
use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;

use function get_debug_type;
use function is_array;
use function is_callable;
use function Safe\sprintf;

/**
 * @internal
 */
final class InternalDeserializationHandler
{
    /** @var callable */
    private $handler;

    public function __construct(callable $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public function __invoke(VisitorInterface $visitor, $data, Type $type, Context $context)
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
