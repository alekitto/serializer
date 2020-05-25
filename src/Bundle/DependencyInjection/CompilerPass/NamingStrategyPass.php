<?php declare(strict_types=1);

namespace Kcs\Serializer\Bundle\DependencyInjection\CompilerPass;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class NamingStrategyPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $namingStrategy = $container->getParameter('kcs_serializer.naming_strategy');

        if ($container->hasDefinition($namingStrategy)) {
            $container->setAlias('kcs_serializer.naming_strategy', new Alias($namingStrategy));
        } elseif ($container->hasDefinition('kcs_serializer.naming_strategy.'.$namingStrategy)) {
            $container->setAlias('kcs_serializer.naming_strategy', new Alias('kcs_serializer.naming_strategy.'.$namingStrategy));
        } elseif (\class_exists($namingStrategy)) {
            $container->register('kcs_serializer.naming_strategy', $namingStrategy);
        } else {
            throw new InvalidConfigurationException(\sprintf('Unknown naming strategy "%s".', $namingStrategy));
        }
    }
}
