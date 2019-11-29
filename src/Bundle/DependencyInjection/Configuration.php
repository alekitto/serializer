<?php declare(strict_types=1);

namespace Kcs\Serializer\Bundle\DependencyInjection;

use Doctrine\ODM\PHPCR\DocumentManager as PHPCRDocumentManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

class Configuration implements ConfigurationInterface
{
    /**
     * @inheritDoc
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('framework');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->canBeDisabled()
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('metadata')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('property_info')->defaultValue(\class_exists(PropertyInfoExtractor::class))->end()
                        ->booleanNode('doctrine_orm')->defaultValue(\class_exists(EntityManager::class))->end()
                        ->booleanNode('doctrine_phpcr')->defaultValue(\class_exists(PHPCRDocumentManager::class))->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
