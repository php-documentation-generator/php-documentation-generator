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

namespace PhpDocumentGenerator\View;

class ClassView
{
    /**
     * @var ClassView[]
     * @var ConstantView[]
     * @var PropertyView[]
     * @var MethodView[]
     */
    public function __construct(public readonly string $name, public readonly string $description, public readonly ?string $link = null, public readonly array $links = [], public readonly ?self $parentClass = null, public readonly array $interfaces = [], public readonly array $constants = [], public readonly array $properties = [], public readonly array $methods = [], public readonly ?string $type = null, public readonly ?bool $deprecated = null, public readonly ?bool $final = null)
    {
    }
}
