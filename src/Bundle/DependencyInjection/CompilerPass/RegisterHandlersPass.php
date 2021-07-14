<?php

declare(strict_types=1);

namespace Kcs\Serializer\Bundle\DependencyInjection\CompilerPass;

use Kcs\Serializer\Direction;
use Kcs\Serializer\Handler\DeserializationHandlerInterface;
use Kcs\Serializer\Handler\HandlerRegistry;
use Kcs\Serializer\Handler\InternalDeserializationHandler;
use Kcs\Serializer\Handler\InternalSerializationHandler;
use Kcs\Serializer\Handler\SerializationHandlerInterface;
use Kcs\Serializer\Handler\SubscribingHandlerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use function is_subclass_of;
use function sprintf;

class RegisterHandlersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $registryDef = $container->findDefinition('kcs_serializer.handler_registry');
        $handlers = [];

        foreach ($container->findTaggedServiceIds('kcs_serializer.handler') as $serviceId => $unused) {
            $definition = $container->findDefinition($serviceId);

            $class = $definition->getClass();
            if (! is_subclass_of($class, SubscribingHandlerInterface::class, true)) {
                throw new RuntimeException(sprintf('%s is not implementing %s, but is tagged as kcs_serializer.handler', $serviceId, SubscribingHandlerInterface::class));
            }

            foreach ($class::getSubscribingMethods() as $methodData) {
                if (! isset($methodData['type'])) {
                    throw new RuntimeException(sprintf('For each subscribing method a "type" attribute must be given for %s.', $class));
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

        foreach ($container->findTaggedServiceIds('kcs_serializer.serialization_handler') as $serviceId => $unused) {
            $definition = $container->findDefinition($serviceId);
            $class = $definition->getClass();
            if (! is_subclass_of($class, SerializationHandlerInterface::class, true)) {
                throw new RuntimeException(sprintf('%s is not implementing %s, but is tagged as kcs_serializer.serialization_handler', $serviceId, SerializationHandlerInterface::class));
            }

            $type = $class::getType();
            $handlers[Direction::DIRECTION_SERIALIZATION][$type] = new Definition(InternalSerializationHandler::class, [[new ServiceClosureArgument(new Reference($serviceId)), 'serialize']]);
        }

        foreach ($container->findTaggedServiceIds('kcs_serializer.deserialization_handler') as $serviceId => $unused) {
            $definition = $container->findDefinition($serviceId);
            $class = $definition->getClass();
            if (! is_subclass_of($class, DeserializationHandlerInterface::class, true)) {
                throw new RuntimeException(sprintf('%s is not implementing %s, but is tagged as kcs_serializer.deserialization_handler', $serviceId, DeserializationHandlerInterface::class));
            }

            $type = $class::getType();
            $handlers[Direction::DIRECTION_DESERIALIZATION][$type] = new Definition(InternalDeserializationHandler::class, [[new ServiceClosureArgument(new Reference($serviceId)), 'deserialize']]);
        }

        $registryDef->setArgument(0, $handlers);
    }
}
