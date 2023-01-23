<?php
declare(strict_types=1);

namespace ApiPlatform\PDGBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class ApiPlatformPDGExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
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
