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
                        ->arrayNode('remote')
                            ->children()
                                ->scalarNode('enabled')->isRequired()->end()
                                ->scalarNode('url')->isRequired()->end()
                            ->end()
                        ->end()
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
                ->arrayNode('delay_alert')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('application')->defaultValue('')->end()
                        ->scalarNode('remote_url')->defaultValue(null)->end()
                        ->arrayNode('routes')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('name')->isRequired()->end()
                                    ->scalarNode('seconds')->isRequired()->end()
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('destination_name')->defaultValue('')->end()
                        ->scalarNode('destination_address')->defaultValue('')->end()
                        ->scalarNode('attach_trace_logger')->defaultValue(false)->end()
                        ->scalarNode('attach_request_info')->defaultValue(false)->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}