<?php declare(strict_types=1);

namespace Kcs\Serializer\Bundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterHandlersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $registryDef = $container->findDefinition('kcs_serializer.handler_registry');
        foreach ($container->findTaggedServiceIds('kcs_serializer.handler') as $serviceId => $unused) {
            $definition = $container->findDefinition($serviceId);
            $definition->setLazy(true);

            $registryDef->addMethodCall('registerSubscribingHandler', [new Reference($serviceId)]);
        }
    }
}
