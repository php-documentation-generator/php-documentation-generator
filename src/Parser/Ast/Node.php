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

namespace PhpDocumentGenerator\Parser\Ast;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;

final class Node
{
    public function __construct(private readonly PhpDocTagValueNode $node)
    {
    }

    public function getNode(): PhpDocTagValueNode
    {
        return $this->node;
    }

    public function getDescription(): ?string
    {
        return $this->node->description ?? null;
    }

    public function getName(): string
    {
        return $this->node->type->__toString();
    }
}
