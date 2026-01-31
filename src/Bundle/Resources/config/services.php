<?php

declare(strict_types=1);

use Kcs\Metadata\Loader\ChainLoader;
use Kcs\Metadata\Loader\FilesLoader;
use Kcs\Serializer\Construction\DoctrineObjectConstructor;
use Kcs\Serializer\Construction\InitializedObjectConstructor;
use Kcs\Serializer\Construction\UnserializeObjectConstructor;
use Kcs\Serializer\CsvSerializationVisitor;
use Kcs\Serializer\Direction;
use Kcs\Serializer\EventDispatcher\PreSerializeEvent;
use Kcs\Serializer\EventDispatcher\Subscriber\DoctrineProxySubscriber;
use Kcs\Serializer\GenericDeserializationVisitor;
use Kcs\Serializer\GenericSerializationVisitor;
use Kcs\Serializer\Handler\ArrayCollectionHandler;
use Kcs\Serializer\Handler\ConstraintViolationHandler;
use Kcs\Serializer\Handler\DateHandler;
use Kcs\Serializer\Handler\FormErrorHandler;
use Kcs\Serializer\Handler\HandlerRegistry;
use Kcs\Serializer\Handler\HandlerRegistryInterface;
use Kcs\Serializer\Handler\SymfonyUidHandler;
use Kcs\Serializer\Handler\UuidInterfaceHandler;
use Kcs\Serializer\JsonDeserializationVisitor;
use Kcs\Serializer\JsonSerializationVisitor;
use Kcs\Serializer\Metadata\Loader\XmlLoader;
use Kcs\Serializer\Metadata\Loader\YamlLoader;
use Kcs\Serializer\Metadata\MetadataFactory;
use Kcs\Serializer\Naming\CacheNamingStrategy;
use Kcs\Serializer\Naming\IdenticalPropertyNamingStrategy;
use Kcs\Serializer\Naming\SerializedNameAttributeStrategy;
use Kcs\Serializer\Serializer;
use Kcs\Serializer\SerializerInterface;
use Kcs\Serializer\XmlDeserializationVisitor;
use Kcs\Serializer\XmlSerializationVisitor;
use Kcs\Serializer\YamlDeserializationVisitor;
use Kcs\Serializer\YamlSerializationVisitor;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('kcs_serializer.serializer', Serializer::class)
            ->public()
            ->args([
                service('kcs_serializer.metadata.metadata_factory'),
                service('kcs_serializer.handler_registry'),
                service('kcs_serializer.construction.doctrine'),
                [],
                [],
                service('event_dispatcher'),
            ])

        ->set('kcs_serializer.doctrine_proxy_subscriber', DoctrineProxySubscriber::class)
            ->tag('kernel.event_listener', [
                'event' => PreSerializeEvent::class,
                'method' => 'onPreSerialize',
                'priority' => 20,
            ])

        ->set('kcs_serializer.metadata.loader.xml', FilesLoader::class)
            ->lazy()
            ->args([
                [],
                XmlLoader::class,
            ])

        ->set('kcs_serializer.metadata.loader.yaml', FilesLoader::class)
            ->lazy()
            ->args([
                [],
                YamlLoader::class,
            ])

        ->set('kcs_serializer.metadata.loader', ChainLoader::class)
            ->lazy()
            ->args([
                [],
            ])

        ->set('kcs_serializer.metadata.metadata_factory', MetadataFactory::class)
            ->lazy()
            ->args([
                service('kcs_serializer.metadata.loader'),
                service('event_dispatcher'),
                service('kcs_serializer.metadata.cache')->nullOnInvalid(),
            ])

        ->set('kcs_serializer.handler.date', DateHandler::class)
            ->tag('kcs_serializer.handler')

        ->set('kcs_serializer.handler.constraint_violation', ConstraintViolationHandler::class)
            ->tag('kcs_serializer.handler')

        ->set('kcs_serializer.handler.array_collection', ArrayCollectionHandler::class)
            ->tag('kcs_serializer.handler')

        ->set('kcs_serializer.handler.uuid', UuidInterfaceHandler::class)
            ->tag('kcs_serializer.handler')

        ->set('kcs_serializer.handler.symfony_uid', SymfonyUidHandler::class)
            ->tag('kcs_serializer.handler')

        ->set('kcs_serializer.handler.form_error', FormErrorHandler::class)
            ->args([service('translator')->nullOnInvalid()])
            ->tag('kcs_serializer.handler')

        ->set('kcs_serializer.handler_registry', HandlerRegistry::class)
        ->alias(HandlerRegistryInterface::class, 'kcs_serializer.handler_registry')->public()

        ->set('kcs_serializer.naming.cache_strategy', CacheNamingStrategy::class)
            ->args([service('kcs_serializer.naming.serialized_name_attribute_strategy')])

        ->set('kcs_serializer.naming.serialized_name_attribute_strategy', SerializedNameAttributeStrategy::class)
            ->args([service('kcs_serializer.naming_strategy')])

        ->set('kcs_serializer.naming_strategy.identical', IdenticalPropertyNamingStrategy::class)
        ->alias('kcs_serializer.naming_strategy.identical_property', 'kcs_serializer.naming_strategy.identical')
        ->set('kcs_serializer.naming_strategy.underscore', IdenticalPropertyNamingStrategy::class)

        ->set('kcs_serializer.construction.unserialize', UnserializeObjectConstructor::class)
        ->set('kcs_serializer.construction.initialized_object', InitializedObjectConstructor::class)
            ->args([service('kcs_serializer.construction.unserialize')])
        ->set('kcs_serializer.construction.doctrine', DoctrineObjectConstructor::class)
            ->args([service('kcs_serializer.construction.initialized_object')])

        ->set('kcs_serializer.visitor.prototype')
            ->abstract()
            ->args([service('kcs_serializer.naming.strategy')])

        ->set('kcs_serializer.serialization_visitor.array', GenericSerializationVisitor::class)
            ->parent('kcs_serializer.visitor.prototype')
            ->tag('kcs_serializer.serialization_visitor', ['direction' => Direction::Serialization->toString(), 'format' => 'array'])
        ->set('kcs_serializer.serialization_visitor.xml', XmlSerializationVisitor::class)
            ->parent('kcs_serializer.visitor.prototype')
            ->call('setDefaultEncoding', ['%kcs_serializer.xml_default_encoding%'])
            ->tag('kcs_serializer.serialization_visitor', ['direction' => Direction::Serialization->toString(), 'format' => 'xml'])
        ->set('kcs_serializer.serialization_visitor.json', JsonSerializationVisitor::class)
            ->parent('kcs_serializer.visitor.prototype')
            ->tag('kcs_serializer.serialization_visitor', ['direction' => Direction::Serialization->toString(), 'format' => 'json'])
        ->set('kcs_serializer.serialization_visitor.csv', CsvSerializationVisitor::class)
            ->parent('kcs_serializer.visitor.prototype')
            ->tag('kcs_serializer.serialization_visitor', ['direction' => Direction::Serialization->toString(), 'format' => 'csv'])
        ->set('kcs_serializer.serialization_visitor.yaml', YamlSerializationVisitor::class)
            ->parent('kcs_serializer.visitor.prototype')
            ->tag('kcs_serializer.serialization_visitor', ['direction' => Direction::Serialization->toString(), 'format' => 'yaml'])

        ->set('kcs_serializer.deserialization_visitor.array', GenericDeserializationVisitor::class)
            ->parent('kcs_serializer.visitor.prototype')
            ->tag('kcs_serializer.serialization_visitor', ['direction' => Direction::Deserialization->toString(), 'format' => 'array'])
        ->set('kcs_serializer.deserialization_visitor.xml', XmlDeserializationVisitor::class)
            ->parent('kcs_serializer.visitor.prototype')
            ->tag('kcs_serializer.serialization_visitor', ['direction' => Direction::Deserialization->toString(), 'format' => 'xml'])
        ->set('kcs_serializer.deserialization_visitor.json', JsonDeserializationVisitor::class)
            ->parent('kcs_serializer.visitor.prototype')
            ->tag('kcs_serializer.serialization_visitor', ['direction' => Direction::Deserialization->toString(), 'format' => 'json'])
        ->set('kcs_serializer.deserialization_visitor.yaml', YamlDeserializationVisitor::class)
            ->parent('kcs_serializer.visitor.prototype')
            ->tag('kcs_serializer.serialization_visitor', ['direction' => Direction::Deserialization->toString(), 'format' => 'yaml'])

        ->alias('kcs_serializer', 'kcs_serializer.serializer')->public()
        ->alias('kcs_serializer.naming.strategy', 'kcs_serializer.naming.cache_strategy')->public()

        ->alias(Serializer::class, 'kcs_serializer.serializer')->public()
        ->alias(SerializerInterface::class, 'kcs_serializer.serializer')->public();
};
