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

namespace PhpDocumentGenerator\Twig\View;

class ClassView
{
    /**
     * @var ClassView[]
     * @var ConstantView[]
     * @var PropertyView[]
     * @var MethodView[]
     */
    public function __construct(public readonly string $name, public readonly string $description, public readonly ?string $link = null, public readonly array $links = [], public readonly ?self $parentClass = null, public readonly array $interfaces = [], public readonly array $constants = [], public readonly array $properties = [], public readonly array $methods = [])
    {
    }
}
