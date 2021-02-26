<?php declare(strict_types=1);

namespace Kcs\Serializer\Bundle\DependencyInjection;

use Kcs\Serializer\Handler\DeserializationHandlerInterface;
use Kcs\Serializer\Handler\SerializationHandlerInterface;
use Kcs\Serializer\Handler\SubscribingHandlerInterface;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Parameter;

use function method_exists;

final class SerializerExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        if ($container->getParameter('kernel.debug')) {
            $loader->load('services_debug.xml');
        }

        if (method_exists($container, 'registerForAutoconfiguration')) {
            $container->registerForAutoconfiguration(SubscribingHandlerInterface::class)
                ->addTag('kcs_serializer.handler')
            ;

            $container->registerForAutoconfiguration(SerializationHandlerInterface::class)->addTag('kcs_serializer.serialization_handler');
            $container->registerForAutoconfiguration(DeserializationHandlerInterface::class)->addTag('kcs_serializer.deserialization_handler');
        }

        $container->setParameter('kcs_serializer.xml_default_encoding', $config['xml_default_encoding'] ?? 'UTF-8');
        $container->setParameter('kcs_serializer.naming_strategy', $config['naming_strategy'] ?? 'underscore');

        if (! $container->getParameter('kernel.debug') && \class_exists(AbstractAdapter::class)) {
            $container->register('kcs_serializer.metadata.cache', AdapterInterface::class)
                ->setFactory(AbstractAdapter::class.'::createSystemCache')
                ->addArgument('')
                ->addArgument(0)
                ->addArgument(new Parameter('container.build_id'))
                ->addArgument('%kernel.cache_dir%/kcs_serializer')
            ;
        }

        $container->setParameter('kcs_serializer.metadata_loader.property_info.enabled', $config['metadata']['property_info'] ?? false);
        $container->setParameter('kcs_serializer.metadata_loader.doctrine_orm.enabled', $config['metadata']['doctrine_orm'] ?? false);
        $container->setParameter('kcs_serializer.metadata_loader.doctrine_phpcr.enabled', $config['metadata']['doctrine_phpcr'] ?? false);
    }

    public function getAlias(): string
    {
        return 'kcs_serializer';
    }
}
