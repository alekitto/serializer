<?php

declare(strict_types=1);

namespace Kcs\Serializer\Bundle\DependencyInjection\CompilerPass;

use Kcs\Serializer\Metadata\Loader\AnnotationLoader;
use Kcs\Serializer\Metadata\Loader\AttributesLoader;
use Kcs\Serializer\Metadata\Loader\DoctrinePHPCRTypeLoader;
use Kcs\Serializer\Metadata\Loader\DoctrineTypeLoader;
use Kcs\Serializer\Metadata\Loader\PropertyInfoTypeLoader;
use Kcs\Serializer\Metadata\Loader\ReflectionLoader;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use UnexpectedValueException;

use function assert;
use function dirname;
use function is_string;

class MappingLoaderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $mappingPath = 'Resources/config/serializer';
        $xmlPaths = [];
        $yamlPaths = [];

        $xmlDefinition = $container->getDefinition('kcs_serializer.metadata.loader.xml');
        $yamlDefinition = $container->getDefinition('kcs_serializer.metadata.loader.yaml');

        $loaders = [
            new Reference('kcs_serializer.metadata.loader.yaml'),
            new Reference('kcs_serializer.metadata.loader.xml'),
        ];

        $loadPath = static function (string $path) use (&$xmlPaths, &$yamlPaths): void {
            try {
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
                foreach ($iterator as $fileInfo) {
                    assert($fileInfo instanceof SplFileInfo);
                    $extension = $fileInfo->getExtension();
                    if ($extension === 'xml') {
                        $xmlPaths[] = $fileInfo->getPathname();
                    } elseif ($extension === 'yaml' || $extension === 'yml') {
                        $yamlPaths[] = $fileInfo->getPathname();
                    }
                }
            } catch (UnexpectedValueException) {
                // Directory not found or not a dir.
                // @ignoreException
            }
        };

        /** @phpstan-var array<string, class-string> $bundles */
        $bundles = $container->getParameter('kernel.bundles');
        foreach ($bundles as $bundle) {
            $reflection = new ReflectionClass($bundle);
            $filename = $reflection->getFileName();
            if ($filename === false) {
                continue;
            }

            $loadPath(dirname($filename) . '/' . $mappingPath);
        }

        $projectDir = $container->getParameter('kernel.project_dir');
        assert(is_string($projectDir));
        $loadPath($projectDir . '/config/serializer');

        $xmlDefinition->replaceArgument(0, $xmlPaths);
        $yamlDefinition->replaceArgument(0, $yamlPaths);

        if ($container->has('annotation_reader')) {
            $definition = new Definition(AnnotationLoader::class);
            $definition->addMethodCall('setReader', [new Reference('annotation_reader')]);

            $container->setDefinition('kcs_serializer.metadata.loader.annotations', $definition);
        }

        $definition = new Definition(AttributesLoader::class);
        $container->setDefinition('kcs_serializer.metadata.loader.attributes', $definition);
        if ($container->has('annotation_reader')) {
            $definition->addArgument(new Reference('kcs_serializer.metadata.loader.annotations'));
        }

        $loaders[] = new Reference('kcs_serializer.metadata.loader.attributes');

        $container->getDefinition('kcs_serializer.metadata.loader')
            ->replaceArgument(0, $loaders);

        $container->register('.kcs_serializer.reflection.metadata.loader')
            ->setPublic(false)
            ->setClass(ReflectionLoader::class)
            ->setDecoratedService('kcs_serializer.metadata.loader')
            ->addArgument(new Reference('.kcs_serializer.reflection.metadata.loader.inner'));

        if ($container->hasDefinition('property_info') && $container->getParameter('kcs_serializer.metadata_loader.property_info.enabled')) {
            $container->register('.kcs_serializer.property_info.metadata.loader')
                ->setPublic(false)
                ->setLazy(true)
                ->setClass(PropertyInfoTypeLoader::class)
                ->setDecoratedService('kcs_serializer.metadata.loader')
                ->addArgument(new Reference('.kcs_serializer.property_info.metadata.loader.inner'))
                ->addArgument(new Reference('property_info'));
        }

        if ($container->hasDefinition('doctrine_phpcr') && $container->getParameter('kcs_serializer.metadata_loader.doctrine_phpcr.enabled')) {
            $container->register('.kcs_serializer.doctrine_phpcr.metadata.loader')
                ->setPublic(false)
                ->setLazy(true)
                ->setClass(DoctrinePHPCRTypeLoader::class)
                ->setDecoratedService('kcs_serializer.metadata.loader')
                ->addArgument(new Reference('.kcs_serializer.doctrine_phpcr.metadata.loader.inner'))
                ->addArgument(new Reference('doctrine_phpcr'));
        }

        if (! $container->hasDefinition('doctrine') || ! $container->getParameter('kcs_serializer.metadata_loader.doctrine_orm.enabled')) {
            return;
        }

        $container->register('.kcs_serializer.doctrine.metadata.loader')
            ->setPublic(false)
            ->setLazy(true)
            ->setClass(DoctrineTypeLoader::class)
            ->setDecoratedService('kcs_serializer.metadata.loader')
            ->addArgument(new Reference('.kcs_serializer.doctrine.metadata.loader.inner'))
            ->addArgument(new Reference('doctrine'));
    }
}
