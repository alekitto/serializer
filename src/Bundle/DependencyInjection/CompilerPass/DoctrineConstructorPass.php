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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DoctrineConstructorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $constructorDef = $container->findDefinition('kcs_serializer.construction.doctrine');

        if ($container->has('doctrine')) {
            $constructorDef->addMethodCall('addManagerRegistry', [new Reference('doctrine')]);
        }

        if ($container->has('doctrine_mongodb')) {
            $constructorDef->addMethodCall('addManagerRegistry', [new Reference('doctrine_mongodb')]);
        }

        if ($container->has('doctrine_phpcr')) {
            $constructorDef->addMethodCall('addManagerRegistry', [new Reference('doctrine_phpcr')]);
        }
    }
}
