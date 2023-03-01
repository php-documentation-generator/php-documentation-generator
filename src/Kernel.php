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

namespace PhpDocumentGenerator;

use PhpDocumentGenerator\Configuration\Guides;
use PhpDocumentGenerator\Configuration\References;
use RuntimeException;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Yaml\Yaml;

final class Kernel extends BaseKernel
{
    public function registerBundles(): iterable
    {
        return [];
    }

    public function getCacheDir(): string
    {
        return '/tmp/pdg/cache';
    }

    public function getLogDir(): string
    {
        return '/tmp/pdg/log';
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container): void {
            // Load services
            (new XmlFileLoader($container, new FileLocator(__DIR__.'/../config')))->load('services.xml');

            // Create Application service (required in "pdg" binary)
            $container
                ->register(Application::class, Application::class)
                ->setArgument('$name', 'pdg')
                ->setArgument('$version', '0.0.1')
                ->setPublic(true);

            $container->register('pdg.configuration')->setSynthetic(true);

            // Add each command to the Application service (required for a standalone Symfony console app)
            foreach ($container->findTaggedServiceIds('console.command') as $serviceId => $tags) {
                $container
                    ->getDefinition(Application::class)
                    ->addMethodCall('add', [new Reference($serviceId)]);
            }
        });
    }

    protected function initializeContainer(): void
    {
        parent::initializeContainer();
        $this->container->set('pdg.configuration', $this->loadConfiguration());
    }

    public function loadConfiguration(): Configuration
    {
        // First, load config file from PDG_CONFIG_FILE environment variable
        $configFile = getenv('PDG_CONFIG_FILE');
        if ($configFile && !is_file($configFile)) {
            throw new RuntimeException(sprintf('Configuration file "%s" does not exist.', $configFile));
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
                if (is_file(Path::join($cwd, $filename))) {
                    $configFile = $filename;
                    break;
                }
            }
        }

        // No config file detected
        if (!$configFile) {
            $config = (new Processor())->process(
                $this->getConfigTree(),
                []
            );

            return $this->getConfiguration($config);
        }

        $root = Path::getDirectory($configFile);

        // Config file detected: read it and parse it
        $config = (new Processor())->process(
            $this->getConfigTree(),
            Yaml::parse(file_get_contents($configFile))
        );

        if (isset($config['autoload'])) {
            // Autoload project autoloader
            $autoload = Path::join($root, $config['autoload']);
            if (!file_exists($autoload)) {
                throw new RuntimeException(sprintf('Autoload file "%s" does not exist.', $autoload));
            }
            require_once $autoload;
        }

        return $this->getConfiguration($config);
    }

    private function getConfiguration(array $config): Configuration
    {
        $normalizer = new ObjectNormalizer(nameConverter: new CamelCaseToSnakeCaseNameConverter());

        return new Configuration(autoload: $config['autoload'], guides: $normalizer->denormalize($config['guides'], Guides::class), references: $normalizer->denormalize($config['references'], References::class));
    }

    private function getConfigTree(): NodeInterface
    {
        $treeBuilder = new TreeBuilder('pdg');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('autoload')
                    ->info('Project autoload')
                    ->defaultValue(null)
                ->end()
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
