<?php declare(strict_types=1);

namespace Kcs\Serializer\Bundle\DependencyInjection\CompilerPass;

use Kcs\Serializer\Metadata\Loader\AnnotationLoader;
use Kcs\Serializer\Metadata\Loader\AttributesLoader;
use Kcs\Serializer\Metadata\Loader\DoctrinePHPCRTypeLoader;
use Kcs\Serializer\Metadata\Loader\DoctrineTypeLoader;
use Kcs\Serializer\Metadata\Loader\PropertyInfoTypeLoader;
use Kcs\Serializer\Metadata\Loader\ReflectionLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class MappingLoaderPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
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

        $loadPath = static function (string $path) use (&$xml_paths, &$yaml_paths) {
            try {
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
                /** @var \SplFileInfo $fileInfo */
                foreach ($iterator as $fileInfo) {
                    $extension = $fileInfo->getExtension();
                    if ('xml' === $extension) {
                        $xml_paths[] = $fileInfo->getPathname();
                    } elseif ('yaml' === $extension || 'yml' === $extension) {
                        $yaml_paths[] = $fileInfo->getPathname();
                    }
                }
            } catch (\UnexpectedValueException $e) {
                // Directory not found or not a dir.
            }
        };

        foreach ($container->getParameter('kernel.bundles') as $bundle) {
            $reflection = new \ReflectionClass($bundle);
            $loadPath(\dirname($reflection->getFileName()).'/'.$mappingPath);
        }

        $loadPath($container->getParameter('kernel.project_dir').'/config/serializer');

        $xmlDefinition->replaceArgument(0, $xml_paths);
        $yamlDefinition->replaceArgument(0, $yaml_paths);

        if ($container->has('annotation_reader')) {
            $definition = new Definition(AnnotationLoader::class);
            $definition->addMethodCall('setReader', [new Reference('annotation_reader')]);

            $container->setDefinition('kcs_serializer.metadata.loader.annotations', $definition);
        }

        if (PHP_VERSION_ID >= 80000) {
            $definition = new Definition(AttributesLoader::class);
            $container->setDefinition('kcs_serializer.metadata.loader.attributes', $definition);
            if ($container->has('annotation_reader')) {
                $definition->addArgument(new Reference('kcs_serializer.metadata.loader.annotations'));
            }

            $loaders[] = new Reference('kcs_serializer.metadata.loader.attributes');
        } else {
            $loaders[] = new Reference('kcs_serializer.metadata.loader.annotations');
        }

        $container->getDefinition('kcs_serializer.metadata.loader')
            ->replaceArgument(0, $loaders)
        ;

        if (PHP_VERSION_ID >= 70400) {
            $container->register('.kcs_serializer.reflection.metadata.loader')
                ->setPublic(false)
                ->setClass(ReflectionLoader::class)
                ->setDecoratedService('kcs_serializer.metadata.loader')
                ->addArgument(new Reference('.kcs_serializer.reflection.metadata.loader.inner'))
            ;
        }

        if ($container->hasDefinition('property_info') && $container->getParameter('kcs_serializer.metadata_loader.property_info.enabled')) {
            $container->register('.kcs_serializer.property_info.metadata.loader')
                ->setPublic(false)
                ->setLazy(true)
                ->setClass(PropertyInfoTypeLoader::class)
                ->setDecoratedService('kcs_serializer.metadata.loader')
                ->addArgument(new Reference('.kcs_serializer.property_info.metadata.loader.inner'))
                ->addArgument(new Reference('property_info'))
            ;
        }

        if ($container->hasDefinition('doctrine_phpcr') && $container->getParameter('kcs_serializer.metadata_loader.doctrine_phpcr.enabled')) {
            $container->register('.kcs_serializer.doctrine_phpcr.metadata.loader')
                ->setPublic(false)
                ->setLazy(true)
                ->setClass(DoctrinePHPCRTypeLoader::class)
                ->setDecoratedService('kcs_serializer.metadata.loader')
                ->addArgument(new Reference('.kcs_serializer.doctrine_phpcr.metadata.loader.inner'))
                ->addArgument(new Reference('doctrine_phpcr'))
            ;
        }

        if ($container->hasDefinition('doctrine') && $container->getParameter('kcs_serializer.metadata_loader.doctrine_orm.enabled')) {
            $container->register('.kcs_serializer.doctrine.metadata.loader')
                ->setPublic(false)
                ->setLazy(true)
                ->setClass(DoctrineTypeLoader::class)
                ->setDecoratedService('kcs_serializer.metadata.loader')
                ->addArgument(new Reference('.kcs_serializer.doctrine.metadata.loader.inner'))
                ->addArgument(new Reference('doctrine'))
            ;
        }
    }
}
