<?php

declare(strict_types=1);

namespace Kcs\Serializer\Bundle\DependencyInjection;

use Kcs\Serializer\Adapter\Symfony\SymfonyCompiledSerializationDescriptorCache;
use Kcs\Serializer\Handler\DeserializationHandlerInterface;
use Kcs\Serializer\Handler\SerializationHandlerInterface;
use Kcs\Serializer\Handler\SubscribingHandlerInterface;
use Kcs\Serializer\Serialization\Compiled\CompiledJsonSerializationVisitor;
use Kcs\Serializer\Serialization\Compiled\CompiledSerializationVisitor;
use Kcs\Serializer\Serialization\Compiled\CompiledXmlSerializationVisitor;
use Kcs\Serializer\Serialization\Compiled\CompiledYamlSerializationVisitor;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;

use function class_exists;

final class SerializerExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');

        if ($container->getParameter('kernel.debug')) {
            $loader->load('services_debug.php');
        }

        $container->registerForAutoconfiguration(SubscribingHandlerInterface::class)
            ->addTag('kcs_serializer.handler');

        $container->registerForAutoconfiguration(SerializationHandlerInterface::class)->addTag('kcs_serializer.serialization_handler');
        $container->registerForAutoconfiguration(DeserializationHandlerInterface::class)->addTag('kcs_serializer.deserialization_handler');

        $container->setParameter('kcs_serializer.xml_default_encoding', $config['xml_default_encoding'] ?? 'UTF-8');
        $container->setParameter('kcs_serializer.naming_strategy', $config['naming_strategy'] ?? 'underscore');

        if (! $container->getParameter('kernel.debug') && class_exists(AbstractAdapter::class)) {
            $container->register('kcs_serializer.metadata.cache', AdapterInterface::class)
                ->setFactory(AbstractAdapter::class . '::createSystemCache')
                ->addArgument('')
                ->addArgument(0)
                ->addArgument(new Parameter('container.build_id'))
                ->addArgument('%kernel.cache_dir%/kcs_serializer');
        }

        $container->setParameter('kcs_serializer.metadata_loader.property_info.enabled', $config['metadata']['property_info'] ?? false);
        $container->setParameter('kcs_serializer.metadata_loader.doctrine_orm.enabled', $config['metadata']['doctrine_orm'] ?? false);
        $container->setParameter('kcs_serializer.metadata_loader.doctrine_phpcr.enabled', $config['metadata']['doctrine_phpcr'] ?? false);

        $container->setParameter('kcs_serializer.debug.logger', $config['debug']['logger'] ?? 'logger');

        if (! ($config['compiled_serialization']['enabled'] ?? false)) {
            return;
        }

        $this->enableCompiledSerialization($container, $config['compiled_serialization']);
    }

    public function getAlias(): string
    {
        return 'kcs_serializer';
    }

    /** @param array<string, mixed> $config */
    private function enableCompiledSerialization(ContainerBuilder $container, array $config): void
    {
        $cacheReference = null;
        if (($config['cache']['enabled'] ?? true) && class_exists(SymfonyCompiledSerializationDescriptorCache::class)) {
            $container->register('kcs_serializer.compiled_serialization.descriptor_cache', SymfonyCompiledSerializationDescriptorCache::class)
                ->setArguments([
                    $config['cache']['directory'],
                    '%kernel.debug%',
                ]);

            $cacheReference = new Reference('kcs_serializer.compiled_serialization.descriptor_cache');
        }

        $this->configureCompiledSerializationVisitor(
            $container,
            'kcs_serializer.serialization_visitor.array',
            CompiledSerializationVisitor::class,
            $cacheReference,
        );
        $this->configureCompiledSerializationVisitor(
            $container,
            'kcs_serializer.serialization_visitor.json',
            CompiledJsonSerializationVisitor::class,
            $cacheReference,
        );
        $this->configureCompiledSerializationVisitor(
            $container,
            'kcs_serializer.serialization_visitor.xml',
            CompiledXmlSerializationVisitor::class,
            $cacheReference,
        );
        $this->configureCompiledSerializationVisitor(
            $container,
            'kcs_serializer.serialization_visitor.yaml',
            CompiledYamlSerializationVisitor::class,
            $cacheReference,
        );
    }

    private function configureCompiledSerializationVisitor(ContainerBuilder $container, string $serviceId, string $className, Reference|null $cacheReference): void
    {
        $definition = $container->getDefinition($serviceId);
        $definition->setClass($className);
        if ($cacheReference === null) {
            return;
        }

        $definition->addMethodCall('setCompiledSerializationDescriptorCache', [$cacheReference]);
    }
}
