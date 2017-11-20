<?php declare(strict_types=1);

/*
 * Copyright 2017 Alessandro Chitolina <alekitto@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Kcs\Serializer\Bundle\DependencyInjection\CompilerPass;

use Kcs\Serializer\Metadata\Loader\AnnotationLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class MappingLoaderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $mappingPath = 'Resources/config/serializer';
        $xml_paths = [];
        $yaml_paths = [];

        $xmlDefinition = $container->getDefinition('kcs_serializer.metadata.loader.xml');
        $yamlDefinition = $container->getDefinition('kcs_serializer.metadata.loader.yaml');

        $loaders = [
            new Reference('kcs_serializer.metadata.loader.yaml'),
            new Reference('kcs_serializer.metadata.loader.xml'),
        ];

        foreach ($container->getParameter('kernel.bundles') as $bundle) {
            $reflection = new \ReflectionClass($bundle);
            $xml_paths = array_merge($xml_paths, glob(dirname($reflection->getFileName()).'/'.$mappingPath.'/*.xml'));
            $yaml_paths = array_merge($yaml_paths, glob(dirname($reflection->getFileName()).'/'.$mappingPath.'/*.yml'));
        }

        $xmlDefinition->replaceArgument(0, $xml_paths);
        $yamlDefinition->replaceArgument(0, $yaml_paths);

        if ($container->has('annotation_reader')) {
            $definition = new Definition(AnnotationLoader::class);
            $definition->addMethodCall('setReader', [new Reference('annotation_reader')]);
            $container->setDefinition('kcs_serializer.metadata.loader.annotations', $definition);

            $loaders[] = new Reference('kcs_serializer.metadata.loader.annotations');
        }

        $container->getDefinition('kcs_serializer.metadata.loader')
            ->replaceArgument(0, $loaders);
    }
}
