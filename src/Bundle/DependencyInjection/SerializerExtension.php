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

namespace Kcs\Serializer\Bundle\DependencyInjection;

use Doctrine\Common\Cache\FilesystemCache;
use Kcs\Serializer\Handler\SubscribingHandlerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SerializerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        if (method_exists($container, 'registerForAutoconfiguration')) {
            $container->registerForAutoconfiguration(SubscribingHandlerInterface::class)
                ->addTag('kcs_serializer.handler');
        }

        if (class_exists(FilesystemCache::class)) {
            $container->register('kcs_serializer.metadata.cache', FilesystemCache::class)
                ->addArgument('%kernel.cache_dir%/kcs_serializer');
        }
    }
}
