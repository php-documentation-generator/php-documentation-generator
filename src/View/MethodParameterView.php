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

class MethodParameterView
{
    /**
     * @var TypeView[]
     */
    public function __construct(public readonly string $name, public readonly array $types = [], public readonly bool $isReference = false, public readonly ?string $defaultValue = null, public readonly ?string $description = null)
    {
    }
}
