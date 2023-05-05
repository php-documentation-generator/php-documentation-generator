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

namespace PhpDocumentGenerator\Playground\Command;

use DAMA\DoctrineTestBundle\PHPUnit\PHPUnitExtension;
use PHPUnit\Framework\TestSuite;
use PHPUnit\TextUI\Command;
use PHPUnit\TextUI\XmlConfiguration\Extension;

class PhpUnitCommand extends Command
{
    public static $suite;

    public static function setSuite(TestSuite $suite): void
    {
        self::$suite = $suite;
    }

    /**
     * Custom callback for test suite discovery.
     */
    protected function handleCustomTestSuite(): void
    {
        $this->arguments['test'] = self::$suite;
        $ext = new Extension(PHPUnitExtension::class, '', []);
        $this->arguments['extensions'] = [$ext];
    }
}
