<?php declare(strict_types=1);

namespace Kcs\Serializer\Handler;

use Kcs\Serializer\Direction;
use Kcs\Serializer\Exception\LogicException;
use Kcs\Serializer\Exception\RuntimeException;

class HandlerRegistry implements HandlerRegistryInterface
{
    /**
     * @var SubscribingHandlerInterface[]
     */
    protected $handlers;

    public static function getDefaultMethod(int $direction, string $type): string
    {
        if (false !== $pos = strrpos($type, '\\')) {
            $type = substr($type, $pos + 1);
        }

        switch ($direction) {
            case Direction::DIRECTION_DESERIALIZATION:
                return 'deserialize'.$type;

            case Direction::DIRECTION_SERIALIZATION:
                return 'serialize'.$type;

            default:
                throw new LogicException(sprintf(
                    'The direction %u does not exist; see GraphNavigator constants.', $direction
                ));
        }
    }

    public function __construct(array $handlers = [])
    {
        $this->handlers = $handlers;
    }

    /**
     * {@inheritdoc}
     */
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
                $this->registerHandler($direction, $methodData['type'], [$handler, $method]);
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

    /**
     * {@inheritdoc}
     */
    public function getHandler(int $direction, string $typeName): ?callable
    {
        if (! isset($this->handlers[$direction][$typeName])) {
            return null;
        }

        $v = &$this->handlers[$direction][$typeName];
        if (\is_array($v) && isset($v[0]) && $v[0] instanceof \Closure) {
            $v[0] = $v[0]();
        }

        return $v;
    }
}
