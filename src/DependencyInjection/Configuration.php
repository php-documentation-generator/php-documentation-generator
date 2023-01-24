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
                ->arrayNode('reference')
                    ->children()
                        ->scalarNode('src')
                        ->isRequired()
                        ->defaultValue('%kernel.project_dir%/src')
                        ->end()
                        ->scalarNode('namespace')
                        ->isRequired()
                        ->defaultValue('App')
                        ->end()
                        ->arrayNode('patterns')
                            ->children()
                                ->arrayNode('directories')
                                    ->scalarPrototype()->end()
                                ->end()
                                ->arrayNode('names')
                                    ->scalarPrototype()->end()
                                ->end()
                                ->arrayNode('exclude')
                                    ->scalarPrototype()->end()
                                ->end()
                                ->arrayNode('class_tags_to_ignore')
                                    ->scalarPrototype()->end()
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
                                ->isRequired()
                                ->defaultValue('pages/guide')
                                ->end()
                                ->scalarNode('reference_path')
                                ->isRequired()
                                ->defaultValue('pages/reference')
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('basePath')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
