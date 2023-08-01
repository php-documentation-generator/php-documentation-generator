<?php

/*
 * This file is part of the PHP Documentation Generator project
 *
 * (c) Antoine Bluchet <soyuka@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpDocumentGenerator;

use PhpDocumentGenerator\Configuration\Guides;
use PhpDocumentGenerator\Configuration\References;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Yaml\Yaml;

final class Configuration
{
    public function __construct(public Guides $guides = new Guides(), public References $references = new References())
    {
    }

    public static function parse(string $configFile = null): static
    {
        // First, load config file from PDG_CONFIG_FILE environment variable
        $configFile = $configFile ?? getenv('PDG_CONFIG_FILE');
        if ($configFile && !is_file($configFile)) {
            throw new \RuntimeException(sprintf('Configuration file "%s" does not exist.', $configFile));
        }

        // If PDG_CONFIG_FILE environment variable is not set, try to load config file from default ordered ones
        if (!$configFile) {
            $files = [
                'pdg.config.yaml',
                'pdg.config.yml',
                'pdg.config.dist.yaml',
                'pdg.config.dist.yml',
            ];

            $cwd = getcwd();

            foreach ($files as $filename) {
                if (is_file($filePath = Path::join($cwd, $filename))) {
                    $configFile = $filePath;
                    break;
                }
            }
        }

        // No config file detected
        if (!$configFile) {
            return self::getConfiguration(self::process());
        }

        // Config file detected: read it and parse it
        $config = self::process(
            Yaml::parse(file_get_contents($configFile))
        );

        return self::getConfiguration($config);
    }

    private static function process(array $raw = []): array
    {
        return (new Processor())->process(
            self::getConfigTree(),
            $raw
        );
    }

    private static function getConfiguration(array $config): self
    {
        $normalizer = new ObjectNormalizer(nameConverter: new CamelCaseToSnakeCaseNameConverter());

        return new self(guides: $normalizer->denormalize($config['guides'], Guides::class), references: $normalizer->denormalize($config['references'], References::class));
    }

    private static function getConfigTree(): NodeInterface
    {
        $treeBuilder = new TreeBuilder('pdg');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('references')
                    ->addDefaultsIfNotSet()
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
                        ->arrayNode('exclude')
                            ->info('Files or directories to ignore (supports glob pattern syntax)')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                        ->end()
                        ->arrayNode('exclude_path')
                            ->info('Files or directories to exclude (regex)')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                        ->end()
                        ->arrayNode('tags_to_ignore')
                            ->info('PHP tags to ignore')
                            ->scalarPrototype()->end()
                            ->defaultValue(['@internal', '@experimental', '@ignore'])
                        ->end()
                        ->scalarNode('output')
                            ->info('Path to output generated guide files')
                            ->defaultValue('docs/references')
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('base_url')
                            ->info('Base url for link generation (e.g.: "/docs/references", "docs/references", "https://github.com/foo/bar/blob/main/docs/references")')
                            ->defaultValue('docs/references')
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('guides')
                    ->addDefaultsIfNotSet()
                    ->info('Guides configuration')
                    ->children()
                        ->scalarNode('src')
                            ->info('Root directory for guides')
                            ->defaultValue('guides')
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('output')
                            ->info('Path to output generated reference files')
                            ->defaultValue('docs/guides')
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('base_url')
                            ->info('Base url for link generation (e.g.: "/docs/guides", "docs/guides", "https://github.com/foo/bar/blob/main/docs/guides")')
                            ->defaultValue('docs/guides')
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder->buildTree();
    }
}
