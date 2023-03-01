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

class PropertyView
{
    /**
     * @param $types TypeView[]
     * @param $accessors string[]
     */
    public function __construct(
        public readonly string $name,
        public readonly string $modifier,
        public readonly ?string $description = null,
        public readonly ?string $defaultValue = null,
        public readonly array $types = [],
        public readonly array $accessors = [],
    ) {
    }
}
