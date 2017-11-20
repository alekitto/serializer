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
