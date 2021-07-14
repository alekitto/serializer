<?php

declare(strict_types=1);

namespace Kcs\Serializer\Bundle;

use Kcs\Serializer\Bundle\DependencyInjection\CompilerPass\DoctrineConstructorPass;
use Kcs\Serializer\Bundle\DependencyInjection\CompilerPass\MappingLoaderPass;
use Kcs\Serializer\Bundle\DependencyInjection\CompilerPass\NamingStrategyPass;
use Kcs\Serializer\Bundle\DependencyInjection\CompilerPass\RegisterHandlersPass;
use Kcs\Serializer\Bundle\DependencyInjection\CompilerPass\RegisterVisitorsPass;
use Kcs\Serializer\Bundle\DependencyInjection\SerializerExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SerializerBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container
            ->addCompilerPass(new RegisterVisitorsPass())
            ->addCompilerPass(new NamingStrategyPass())
            ->addCompilerPass(new RegisterHandlersPass())
            ->addCompilerPass(new MappingLoaderPass())
            ->addCompilerPass(new DoctrineConstructorPass());
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return new SerializerExtension();
    }
}
