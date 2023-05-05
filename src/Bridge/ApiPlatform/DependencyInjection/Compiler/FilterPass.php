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

namespace PhpDocumentGenerator\Bridge\ApiPlatform\DependencyInjection\Compiler;

use ApiPlatform\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Injects API Platform filters.
 */
final class FilterPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function process(ContainerBuilder $container): void
    {
        $container
            ->getDefinition('api_platform.filter_locator')
            ->addArgument($this->findAndSortTaggedServices('api_platform.filter', $container));
    }
}
