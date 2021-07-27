<?php

declare(strict_types=1);

namespace Kcs\Serializer\Bundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DoctrineConstructorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $constructorDef = $container->findDefinition('kcs_serializer.construction.doctrine');

        if ($container->has('doctrine')) {
            $constructorDef->addMethodCall('addManagerRegistry', [new Reference('doctrine')]);
        }

        if ($container->has('doctrine_mongodb')) {
            $constructorDef->addMethodCall('addManagerRegistry', [new Reference('doctrine_mongodb')]);
        }

        if (! $container->has('doctrine_phpcr')) {
            return;
        }

        $constructorDef->addMethodCall('addManagerRegistry', [new Reference('doctrine_phpcr')]);
    }
}
