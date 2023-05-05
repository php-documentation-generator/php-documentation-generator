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

namespace PhpDocumentGenerator\Bridge\ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\MapDecorated;

#[AsDecorator(decorates: 'api_platform.metadata.resource.name_collection_factory')]
final class StaticResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    /**
     * @param class-string[] $classes
     */
    public function __construct(private readonly array $classes, #[MapDecorated] private readonly ?ResourceNameCollectionFactoryInterface $decorated = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function create(): ResourceNameCollection
    {
        $classes = $this->classes;
        if ($this->decorated) {
            foreach ($this->decorated->create() as $resourceClass) {
                $classes[] = $resourceClass;
            }
        }

        return new ResourceNameCollection($this->classes);
    }
}
