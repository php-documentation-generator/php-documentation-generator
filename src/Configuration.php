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

namespace PhpDocumentGenerator;

use PhpDocumentGenerator\Configuration\Guides;
use PhpDocumentGenerator\Configuration\References;

final class Configuration
{
    public function __construct(public ?string $autoload = null, public Guides $guides = new Guides(), public References $references = new References())
    {
    }
}
