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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return function (ContainerConfigurator $configurator): void {
    // default configuration for services in *this* file
    $services = $configurator->services()
        ->defaults()
            ->autowire()      // Automatically injects dependencies in your services.
            ->autoconfigure() // Automatically registers your services as commands, event subscribers, etc.
;

    // makes classes in src/ available to be used as services
    // this creates a service per class whose id is the fully-qualified class name
    $services->load('ApiPlatform\\PDGBundle\\', '../src/')
        ->exclude('../src/{DependencyInjection,Entity,Kernel.php, config.php}');

    // order is important in this file because service definitions
    // always *replace* previous ones; add your own service configuration below
};
