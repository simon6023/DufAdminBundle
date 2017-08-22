<?php

namespace Duf\AdminBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder    = new TreeBuilder();
        $rootNode       = $treeBuilder->root('duf_admin');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        $rootNode
            ->children()
                ->scalarNode('site_name')
                    ->defaultValue(null)
                ->end()
                ->booleanNode('ecommerce_enabled')
                    ->defaultValue(false)
                ->end()
                ->booleanNode('aggregator_enabled')
                    ->defaultValue(false)
                ->end()
                ->booleanNode('cron_enabled')
                    ->defaultValue(false)
                ->end()
                ->scalarNode('cron_delay')
                    ->defaultValue(1)
                ->end()
                ->scalarNode('user_entity')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('user_role_entity')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('language_entity')
                    ->defaultValue(null)
                ->end()
                ->arrayNode('sidebar_sections')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('id')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('entities')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('title')->end()
                            ->scalarNode('icon')->end()
                            ->scalarNode('title_field')->end()
                            ->scalarNode('hidden')->end()
                            ->scalarNode('override_route')->end()
                            ->booleanNode('is_tree')->end()
                            ->booleanNode('is_exportable')->end()
                            ->variableNode('acl')->end()
                            ->variableNode('callbacks')->end()
                            ->variableNode('sidebar_section')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('ecommerce_entities')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('title')->end()
                            ->scalarNode('icon')->end()
                            ->scalarNode('title_field')->end()
                            ->scalarNode('hidden')->end()
                            ->scalarNode('override_route')->end()
                            ->booleanNode('is_tree')->end()
                            ->booleanNode('is_product')->end()
                            ->booleanNode('is_store')->end()
                            ->booleanNode('is_exportable')->end()
                            ->variableNode('acl')->end()
                            ->variableNode('callbacks')->end()
                            ->variableNode('sidebar_section')->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('ecommerce_cart_entity')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('ecommerce_cart_redirect_route')
                    ->defaultValue(null)
                ->end()
                ->arrayNode('user_embed_entities')
                    ->beforeNormalization()
                        ->ifTrue(function ($v) { return !is_array($v); })
                        ->then(function ($v) { return array($v); })
                    ->end()
                    ->prototype('scalar')->end()
                ->end()
                ->scalarNode('upload_dir')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('file_system_items_per_page')
                    ->defaultValue(null)
                ->end()
                ->arrayNode('allowed_upload_extensions')
                    ->prototype('array')
                        ->prototype('scalar')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('redmine')
                    ->prototype('scalar')
                    ->end()
                ->end()
                ->arrayNode('admin_assets')
                    ->prototype('array')
                        ->prototype('scalar')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
