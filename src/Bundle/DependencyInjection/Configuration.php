<?php

declare(strict_types=1);

namespace Kcs\Serializer\Bundle\DependencyInjection;

use Doctrine\ODM\PHPCR\DocumentManager as PHPCRDocumentManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

use function assert;
use function class_exists;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('kcs_serializer');
        $rootNode = $treeBuilder->getRootNode();
        assert($rootNode instanceof ArrayNodeDefinition);

        $rootNode->canBeDisabled();
        $rootNode->addDefaultsIfNotSet();

        $children = $rootNode->children();
        $children->scalarNode('xml_default_encoding')->defaultValue('UTF-8')->end();
        $children->scalarNode('naming_strategy')->defaultValue('underscore')->end();

        $debugNode = $children->arrayNode('debug');
        $debugNode->addDefaultsIfNotSet();
        $debugChildren = $debugNode->children();
        $debugChildren->scalarNode('logger')->defaultValue('logger')->end();
        $debugChildren->end();

        $metadataNode = $children->arrayNode('metadata');
        $metadataNode->addDefaultsIfNotSet();
        $metadataChildren = $metadataNode->children();
        $metadataChildren->booleanNode('property_info')->defaultValue(class_exists(PropertyInfoExtractor::class))->end();
        $metadataChildren->booleanNode('doctrine_orm')->defaultValue(class_exists(EntityManager::class))->end();
        $metadataChildren->booleanNode('doctrine_phpcr')->defaultValue(class_exists(PHPCRDocumentManager::class))->end();
        $metadataChildren->end();

        $compiledSerializationNode = $children->arrayNode('compiled_serialization');
        $compiledSerializationNode->addDefaultsIfNotSet();
        $compiledSerializationChildren = $compiledSerializationNode->children();
        $compiledSerializationChildren->booleanNode('enabled')->defaultFalse()->end();
        $compiledSerializationCacheNode = $compiledSerializationChildren->arrayNode('cache');
        $compiledSerializationCacheNode->addDefaultsIfNotSet();
        $compiledSerializationCacheChildren = $compiledSerializationCacheNode->children();
        $compiledSerializationCacheChildren->booleanNode('enabled')->defaultTrue()->end();
        $compiledSerializationCacheChildren->scalarNode('directory')->defaultValue('%kernel.cache_dir%/kcs_serializer/compiled_serialization')->end();
        $compiledSerializationCacheChildren->end();
        $compiledSerializationChildren->end();

        $children->end();

        return $treeBuilder;
    }
}
