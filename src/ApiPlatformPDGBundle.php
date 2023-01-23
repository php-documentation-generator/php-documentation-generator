<?php
declare(strict_types=1);

namespace ApiPlatform\PDGBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class ApiPlatformPDGBundle extends AbstractBundle
{

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->arrayNode('reference')
                ->children()
                ->scalarNode('src')->end()
                ->arrayNode('patterns')
                    ->children()
                    ->arrayNode('directories')->end()
                    ->arrayNode('names')->end()
                    ->arrayNode('exclude')->end()
                    ->arrayNode('class_tags_to_ignore')->end()
                ->end()
            ->end()
            ->arrayNode('sidebar')
                ->children()
                ->arrayNode('directories')
                    ->children()
                    ->arrayNode('Guides')->end()
                    ->arrayNode('Reference')->end()
                ->end()
                ->scalarNode('basePath')->end()
            ->end();

    }

}
