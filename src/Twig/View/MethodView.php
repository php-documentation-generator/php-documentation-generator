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

class MethodView
{
    /**
     * @param TypeView[] $returnTypes
     */
    public function __construct(public readonly string $name, public readonly string $modifier, public readonly array $parameters = [], public readonly array $returnTypes = [], public readonly string $description = '', public readonly array $throws = [])
    {
    }
}
