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
use ReflectionProperty;

class PromotedPropertyDefaultValueNodeVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly ReflectionProperty $reflection,
        public mixed $defaultValue = null
    ) {
    }

    /**
     * @return null
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\ClassMethod && '__construct' === $node->name->name) {
            foreach ($node->getParams() as $param) {
                if ($param->var->name === $this->reflection->getName()) {
                    $this->defaultValue = $param->default;

                    return null;
                }
            }
        }

        return null;
    }
}
