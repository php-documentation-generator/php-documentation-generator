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

namespace PhpDocumentGenerator\Configuration;

final class References
{
    public ?string $baseUrl = null;
    /** @var string[] */
    public array $tagsToIgnore = [];
    /** @var string[] */
    public array $exclude = [];
    public array $excludePath = [];
    public ?string $namespace = null;
    public ?string $output = null;
    public ?string $src = null;
}
