<?php declare(strict_types=1);

namespace Kcs\Serializer\Bundle;

use Kcs\Serializer\Bundle\DependencyInjection\CompilerPass\DoctrineConstructorPass;
use Kcs\Serializer\Bundle\DependencyInjection\CompilerPass\MappingLoaderPass;
use Kcs\Serializer\Bundle\DependencyInjection\CompilerPass\RegisterHandlersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SerializerBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container
            ->addCompilerPass(new RegisterHandlersPass())
            ->addCompilerPass(new MappingLoaderPass())
            ->addCompilerPass(new DoctrineConstructorPass())
        ;
    }
}
