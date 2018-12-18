<?php declare(strict_types=1);

namespace Kcs\Serializer\Bundle\DependencyInjection\CompilerPass;

use Kcs\Serializer\Direction;
use Kcs\Serializer\Handler\HandlerRegistry;
use Kcs\Serializer\Handler\SubscribingHandlerInterface;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterHandlersPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $registryDef = $container->findDefinition('kcs_serializer.handler_registry');
        $handlers = [];

        foreach ($container->findTaggedServiceIds('kcs_serializer.handler') as $serviceId => $unused) {
            $definition = $container->findDefinition($serviceId);

            $class = $definition->getClass();
            if (! \is_subclass_of($class, SubscribingHandlerInterface::class, true)) {
                throw new \RuntimeException(\sprintf(
                    '%s is not implementing %s, but is tagged as kcs_serializer.handler',
                    $serviceId,
                    SubscribingHandlerInterface::class
                ));
            }

            foreach ($class::getSubscribingMethods() as $methodData) {
                if (! isset($methodData['type'])) {
                    throw new \RuntimeException(\sprintf('For each subscribing method a "type" attribute must be given for %s.', $class));
                }

                $directions = [Direction::DIRECTION_DESERIALIZATION, Direction::DIRECTION_SERIALIZATION];
                if (isset($methodData['direction'])) {
                    $directions = [$methodData['direction']];
                }

                foreach ($directions as $direction) {
                    $method = $methodData['method'] ?? HandlerRegistry::getDefaultMethod($direction, $methodData['type']);
                    $handlers[$direction][$methodData['type']] = [new ServiceClosureArgument(new Reference($serviceId)), $method];
                }
            }
        }

        $registryDef->setArgument(0, $handlers);
    }
}
