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

namespace ApiPlatform\PDGBundle\Services\Reference\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class PropertyDefaultValueNodeVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly \ReflectionProperty $property,
        public mixed $defaultValue = null
    ) {
    }

    /**
     * @return null
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Property) {
            foreach ($node->props as $prop) {
                if ($prop->name->name === $this->property->getName()) {
                    $this->defaultValue = $prop->default;

                    return null;
                }
            }
        }

        return null;
    }
}
