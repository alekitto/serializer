<?php

declare(strict_types=1);

namespace Kcs\Serializer\Handler;

use Closure;
use Kcs\Serializer\Context;
use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;

use function assert;
use function get_debug_type;
use function is_array;
use function is_callable;
use function Safe\sprintf;

/**
 * @internal
 */
final class InternalSerializationHandler
{
    /** @var callable */
    private $handler;

    /**
     * @param mixed[] | callable $handler
     * @phpstan-param array{0: callable, 1: string} | callable $handler
     */
    public function __construct($handler)
    {
        if (is_array($handler) && is_callable($handler[0])) {
            $handler[0] = $handler[0]();
        }

        assert(is_callable($handler));
        $this->handler = $handler;
    }

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public function __invoke(VisitorInterface $visitor, $data, Type $type, Context $context)
    {
        assert($context instanceof SerializationContext);
        if (is_array($this->handler) && $this->handler[0] instanceof Closure) {
            $this->handler[0] = $this->handler[0]();
        }

        if (! is_callable($this->handler)) {
            throw new InvalidArgumentException(sprintf('Invalid serialization handler: callable expected, %s passed', get_debug_type($this->handler)));
        }

        return $this->callVisitor(($this->handler)($data), $context);
    }

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    private function callVisitor($data, SerializationContext $context)
    {
        $visitor = $context->visitor;
        $type = $context->guessType($data);

        switch ($type->name) {
            case 'NULL':
                return $visitor->visitNull($data, $type, $context);

            case 'string':
                return $visitor->visitString($data, $type, $context);

            case 'integer':
            case 'int':
                return $visitor->visitInteger($data, $type, $context);

            case 'boolean':
            case 'bool':
                return $visitor->visitBoolean($data, $type, $context);

            case 'double':
            case 'float':
                return $visitor->visitDouble($data, $type, $context);

            case 'array':
                if ($type->countParams() === 1) {
                    return $visitor->visitArray($data, $type, $context);
                }

                return $visitor->visitHash($data, $type, $context);

            case 'resource':
                throw new RuntimeException('Resources are not supported in serialized data.');

            default:
                throw new RuntimeException('Objects cannot be returned by serialization handlers');
        }
    }
}
