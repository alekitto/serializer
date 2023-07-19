<?php

declare(strict_types=1);

namespace Kcs\Serializer\Bundle\DependencyInjection\CompilerPass;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function class_exists;
use function is_string;
use function sprintf;

class NamingStrategyPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $namingStrategy = $container->getParameter('kcs_serializer.naming_strategy');
        if ($namingStrategy === null || ! is_string($namingStrategy)) {
            $namingStrategy = 'underscore';
        }

        if ($container->hasDefinition($namingStrategy)) {
            $container->setAlias('kcs_serializer.naming_strategy', new Alias($namingStrategy));
        } elseif ($container->hasDefinition('kcs_serializer.naming_strategy.' . $namingStrategy)) {
            $container->setAlias('kcs_serializer.naming_strategy', new Alias('kcs_serializer.naming_strategy.' . $namingStrategy));
        } elseif (class_exists($namingStrategy)) {
            $container->register('kcs_serializer.naming_strategy', $namingStrategy);
        } else {
            throw new InvalidConfigurationException(sprintf('Unknown naming strategy "%s".', $namingStrategy));
        }
    }
}
