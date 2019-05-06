<?php declare(strict_types=1);

namespace Kcs\Serializer\Bundle\DependencyInjection\CompilerPass;

use Kcs\Serializer\Metadata\Loader\AnnotationLoader;
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

        foreach ($container->getParameter('kernel.bundles') as $bundle) {
            $reflection = new \ReflectionClass($bundle);

            try {
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(\dirname($reflection->getFileName()).'/'.$mappingPath));
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
            ->replaceArgument(0, $loaders)
        ;
    }
}
