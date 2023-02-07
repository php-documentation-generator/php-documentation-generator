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

namespace PhpDocumentGenerator\DependencyInjection;

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
                    ->info('Project autoload')
                    ->defaultValue('vendor/autoload.php')
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('reference')
                    ->info('References configuration')
                    ->children()
                        ->scalarNode('src')
                            ->info('Root path for code parsing')
                            ->defaultValue('src')
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('namespace')
                            ->info('Root namespace')
                            ->defaultValue('App')
                            ->cannotBeEmpty()
                        ->end()
                        ->arrayNode('patterns')
                            ->children()
                                ->arrayNode('directories')
                                    ->info('Directories to parse (supports pattern syntax)')
                                    ->scalarPrototype()->end()
                                    ->defaultValue(['*'])
                                ->end()
                                ->arrayNode('names')
                                    ->info('File names to parse (supports pattern syntax)')
                                    ->scalarPrototype()->end()
                                    ->defaultValue(['*.php'])
                                ->end()
                                ->arrayNode('exclude')
                                    ->info('Files or directories to ignore (supports pattern syntax)')
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()
                                ->arrayNode('class_tags_to_ignore')
                                    ->info('PHP tags to ignore')
                                    ->scalarPrototype()->end()
                                    ->defaultValue(['@internal', '@experimental'])
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('base_url')
                            ->info('Base url for link generation (e.g.: "/docs/references", "docs/references", "https://github.com/foo/bar/blob/main/docs/references")')
                            ->defaultValue('docs/references')
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('guide')
                    ->info('Guides configuration')
                    ->children()
                        ->scalarNode('base_url')
                            ->info('Base url for link generation (e.g.: "/docs/guides", "docs/guides", "https://github.com/foo/bar/blob/main/docs/guides")')
                            ->defaultValue('docs/guides')
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('target')
                    ->children()
                        ->arrayNode('directories')
                            ->children()
                                ->scalarNode('guide_path')
                                    ->info('Path to output generated reference files')
                                    ->defaultValue('docs/guides')
                                    ->cannotBeEmpty()
                                ->end()
                                ->scalarNode('reference_path')
                                    ->info('Path to output generated guide files')
                                    ->defaultValue('docs/references')
                                    ->cannotBeEmpty()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
