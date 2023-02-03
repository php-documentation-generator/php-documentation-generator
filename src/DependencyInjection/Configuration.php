<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\PDGBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('api_platform_pdg');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('autoload')
                    ->defaultValue('vendor/autoload.php')
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('reference')
                    ->children()
                        ->scalarNode('src')
                            ->defaultValue('src')
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('namespace')
                            ->defaultValue('App')
                            ->cannotBeEmpty()
                        ->end()
                        ->arrayNode('patterns')
                            ->children()
                                ->arrayNode('directories')
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()
                                ->arrayNode('names')
                                    ->scalarPrototype()->end()
                                    ->defaultValue(['*.php'])
                                ->end()
                                ->arrayNode('exclude')
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()
                                ->arrayNode('class_tags_to_ignore')
                                    ->scalarPrototype()->end()
                                    ->defaultValue(['@internal', '@experimental'])
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('target')
                    ->children()
                        ->arrayNode('directories')
                            ->children()
                                ->scalarNode('guide_path')
                                    ->defaultValue('pages/guide')
                                    ->cannotBeEmpty()
                                ->end()
                                ->scalarNode('reference_path')
                                    ->defaultValue('pages/reference')
                                    ->cannotBeEmpty()
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('base_path')
                            ->defaultValue('/pages')
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
