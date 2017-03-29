<?php

namespace MindbazBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link
 * http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('mindbaz');
        $rootNode
            ->children()
                ->arrayNode('credentials')
                    ->children()
                        ->integerNode('idSite')->isRequired()->end()
                        ->scalarNode('login')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('password')->isRequired()->cannotBeEmpty()->end()
                    ->end()
                ->end()
                ->arrayNode('campaigns')
                    ->useAttributeAsKey('name')
                    ->requiresAtLeastOneElement()
                    ->prototype('scalar')->end()
                ->end()
                ->booleanNode('insertMissingSubscribers')->defaultFalse()->end()
            ->end();

        return $treeBuilder;
    }
}
