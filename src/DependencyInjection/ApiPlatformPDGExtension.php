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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class ApiPlatformPDGExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader->load('helper.xml');
        $loader->load('command.xml');

        $container->setParameter('pdg.reference.src', $config['reference']['src']);
        $container->setParameter('pdg.sidebar.reference_path', $config['sidebar']['directories']['reference'][0]);
        $container->setParameter('pdg.reference.patterns', $config['reference']['patterns']);
    }
}
