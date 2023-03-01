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

namespace PhpDocumentGenerator\Tests\Fixtures\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('app_orm');
        $treeBuilder
            ->getRootNode()
            ->children()
                ->arrayNode('mock')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('path')
                            ->defaultValue('%kernel.project_dir%/tests/mocks')
                        ->end()
                        ->enumNode('mode')
                            ->values(['read', 'write'])
                            ->defaultValue('read')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
