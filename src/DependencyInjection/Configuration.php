<?php


namespace Experteam\ApiBaseBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('experteam_api_redis');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('params')
                    ->children()
                        ->scalarNode('remote_url')->isRequired()->end()
                        ->arrayNode('defaults')
                            ->useAttributeAsKey('name')
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('elk_logger')
                    ->children()
                        ->scalarNode('channel')->isRequired()->end()
                    ->end()
                ->end()
                ->arrayNode('fixtures')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('release')->defaultValue('0')->end()
                    ->end()
                ->end()
                ->arrayNode('timezone')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default')->defaultValue('+00:00')->end()
                    ->end()
                ->end()
                ->arrayNode('appkey')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('enabled')->defaultValue(true)->end()
                    ->end()
                ->end()
                ->arrayNode('etag')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('enabled')->defaultValue(true)->end()
                    ->end()
                ->end()
                ->arrayNode('auth')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('remote_url')->defaultValue(null)->end()
                        ->scalarNode('from_redis')->defaultValue(true)->end()
                        ->scalarNode('status_code')->defaultValue(401)->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}