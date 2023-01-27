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

namespace ApiPlatform\PDGBundle\Tests\Command\App\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('app_orm');
        $treeBuilder
            ->getRootNode()
            ->beforeNormalization()
                ->ifTrue(static fn ($v): bool => \is_string($v['url'] ?? null))
                ->then(static function ($v): array {
                    $debug = $v['enable_profiler'] ?? false;
                    $mock = $v['mock'] ?? ['enabled' => false, 'path' => '%kernel.project_dir%/tests/mocks'];
                    unset($v['enable_profiler'], $v['mock']);

                    return [
                        'enable_profiler' => $debug,
                        'mock' => $mock,
                        'connections' => ['default' => $v],
                    ];
                })
            ->end()
            ->children()
                ->booleanNode('enable_profiler')
                    ->defaultFalse()
                ->end()
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
                ->arrayNode('connections')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('url')
                                ->info('Oracle database url with credentials.')
                                ->cannotBeEmpty()
                                ->isRequired()
                            ->end()
                            ->scalarNode('path')
                                ->info('Directory where the entity configuration is stored.')
                                ->cannotBeEmpty()
                                ->defaultValue('%kernel.project_dir%/src/Entity')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
