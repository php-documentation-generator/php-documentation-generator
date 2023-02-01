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

namespace ApiPlatform\PDGBundle;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
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

            // Add each command to the Application service (required for a standalone Symfony console app)
            foreach ($container->findTaggedServiceIds('console.command') as $serviceId => $tags) {
                $container
                    ->getDefinition(Application::class)
                    ->addMethodCall('add', [new Reference($serviceId)]);
            }
        });
    }
}
