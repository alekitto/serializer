<?php declare(strict_types=1);

namespace Kcs\Serializer\Handler;

use Kcs\Serializer\Direction;
use Kcs\Serializer\Exception\LogicException;
use Kcs\Serializer\Exception\RuntimeException;

class HandlerRegistry implements HandlerRegistryInterface
{
    protected $handlers;

    public static function getDefaultMethod($direction, $type)
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
                throw new LogicException(sprintf('The direction %s does not exist; see GraphNavigator::DIRECTION_??? constants.', json_encode($direction)));
        }
    }

    public function __construct(array $handlers = [])
    {
        $this->handlers = $handlers;
    }

    public function registerSubscribingHandler(SubscribingHandlerInterface $handler)
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
                $method = isset($methodData['method']) ? $methodData['method'] : self::getDefaultMethod($direction, $methodData['type']);
                $this->registerHandler($direction, $methodData['type'], [$handler, $method]);
            }
        }

        return $this;
    }

    public function registerHandler($direction, $typeName, callable $handler)
    {
        if (is_string($direction)) {
            $direction = Direction::parseDirection($direction);
        }

        $this->handlers[$direction][$typeName] = $handler;

        return $this;
    }

    public function getHandler($direction, $typeName)
    {
        if (! isset($this->handlers[$direction][$typeName])) {
            return null;
        }

        return $this->handlers[$direction][$typeName];
    }
}
