<?php declare(strict_types=1);

namespace Kcs\Serializer\Handler;

use Symfony\Component\DependencyInjection\ContainerInterface;

class LazyHandlerRegistry extends HandlerRegistry
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var SubscribingHandlerInterface[]
     */
    private $initializedHandlers = [];

    public function __construct(ContainerInterface $container, array $handlers = [])
    {
        parent::__construct($handlers);
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function registerHandler(int $direction, string $typeName, callable $handler): HandlerRegistryInterface
    {
        parent::registerHandler($direction, $typeName, $handler);
        unset($this->initializedHandlers[$direction][$typeName]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getHandler(int $direction, string $typeName): ?callable
    {
        if (isset($this->initializedHandlers[$direction][$typeName])) {
            return $this->initializedHandlers[$direction][$typeName];
        }

        if (! isset($this->handlers[$direction][$typeName])) {
            return null;
        }

        $handler = $this->handlers[$direction][$typeName];
        if (is_array($handler) && is_string($handler[0]) && $this->container->has($handler[0])) {
            $handler[0] = $this->container->get($handler[0]);
        }

        return $this->initializedHandlers[$direction][$typeName] = $handler;
    }
}
