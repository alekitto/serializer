<?php

declare(strict_types=1);

use Kcs\Serializer\Bundle\DataCollector\SerializerDataCollector;
use Kcs\Serializer\Debug\TraceableHandlerRegistry;
use Kcs\Serializer\Debug\TraceableSerializer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('kcs_serializer.debug.traceable_serializer', TraceableSerializer::class)
            ->decorate('kcs_serializer.serializer')
            ->args([
                service('kcs_serializer.debug.traceable_serializer.inner'),
                service('var_dumper.cloner')->nullOnInvalid(),
            ])

        ->set(SerializerDataCollector::class)
            ->args([
                service('kcs_serializer.serializer'),
                service('kcs_serializer.handler_registry'),
            ])
            ->tag('data_collector', [
                'id' => 'kcs_serializer',
                'template' => '@Serializer/Profiler/serializer.html.twig',
                'priority' => 120,
            ])

        ->set('kcs_serializer.debug.traceable_handler_registry', TraceableHandlerRegistry::class)
            ->decorate('kcs_serializer.handler_registry')
            ->args([
                service('kcs_serializer.debug.traceable_handler_registry.inner'),
                service('var_dumper.cloner')->nullOnInvalid(),
            ]);
};
