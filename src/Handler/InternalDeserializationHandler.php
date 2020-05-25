<?php declare(strict_types=1);

namespace Kcs\Serializer\Handler;

use Kcs\Serializer\Context;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;

/**
 * @internal
 */
final class InternalDeserializationHandler
{
    private $handler;

    public function __construct(callable $handler)
    {
        $this->handler = $handler;
    }

    public function __invoke(VisitorInterface $visitor, $data, Type $type, Context $context)
    {
        if (\is_array($this->handler) && $this->handler[0] instanceof \Closure) {
            $this->handler[0] = $this->handler[0]();
        }

        return ($this->handler)($data);
    }
}
