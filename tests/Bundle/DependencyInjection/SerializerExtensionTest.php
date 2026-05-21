<?php

declare(strict_types=1);

namespace Kcs\Serializer\Tests\Bundle\DependencyInjection;

use Kcs\Serializer\Adapter\Symfony\SymfonyCompiledSerializationDescriptorCache;
use Kcs\Serializer\Bundle\DependencyInjection\SerializerExtension;
use Kcs\Serializer\Serialization\Compiled\CompiledJsonSerializationVisitor;
use Kcs\Serializer\Serialization\Compiled\CompiledSerializationVisitor;
use Kcs\Serializer\Serialization\Compiled\CompiledXmlSerializationVisitor;
use Kcs\Serializer\Serialization\Compiled\CompiledYamlSerializationVisitor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;

final class SerializerExtensionTest extends TestCase
{
    public function testCompiledSerializationCanBeEnabled(): void
    {
        $container = $this->createContainer();

        (new SerializerExtension())->load([[
            'compiled_serialization' => [
                'enabled' => true,
            ],
        ]], $container);

        self::assertSame(CompiledSerializationVisitor::class, $container->getDefinition('kcs_serializer.serialization_visitor.array')->getClass());
        self::assertSame(CompiledJsonSerializationVisitor::class, $container->getDefinition('kcs_serializer.serialization_visitor.json')->getClass());
        self::assertSame(CompiledXmlSerializationVisitor::class, $container->getDefinition('kcs_serializer.serialization_visitor.xml')->getClass());
        self::assertSame(CompiledYamlSerializationVisitor::class, $container->getDefinition('kcs_serializer.serialization_visitor.yaml')->getClass());

        self::assertTrue($container->hasDefinition('kcs_serializer.compiled_serialization.descriptor_cache'));
        self::assertSame(
            SymfonyCompiledSerializationDescriptorCache::class,
            $container->getDefinition('kcs_serializer.compiled_serialization.descriptor_cache')->getClass(),
        );
        self::assertEquals(
            [
                'setCompiledSerializationDescriptorCache',
                [new Reference('kcs_serializer.compiled_serialization.descriptor_cache')],
            ],
            $container->getDefinition('kcs_serializer.serialization_visitor.array')->getMethodCalls()[0],
        );
    }

    public function testCompiledSerializationCanDisableDescriptorCache(): void
    {
        $container = $this->createContainer();

        (new SerializerExtension())->load([[
            'compiled_serialization' => [
                'enabled' => true,
                'cache' => [
                    'enabled' => false,
                ],
            ],
        ]], $container);

        self::assertSame(CompiledSerializationVisitor::class, $container->getDefinition('kcs_serializer.serialization_visitor.array')->getClass());
        self::assertFalse($container->hasDefinition('kcs_serializer.compiled_serialization.descriptor_cache'));
        self::assertSame([], $container->getDefinition('kcs_serializer.serialization_visitor.array')->getMethodCalls());
    }

    private function createContainer(): ContainerBuilder
    {
        return new ContainerBuilder(new ParameterBag([
            'kernel.debug' => false,
            'kernel.cache_dir' => sys_get_temp_dir(),
            'container.build_id' => 'test',
        ]));
    }
}
