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

namespace PhpDocumentGenerator\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

abstract class AbstractReferencesCommand extends Command
{
    use CommandTrait;

    public function __construct(string $name = null)
    {
        parent::__construct($name);
    }

    /**
     * @return array<string, SplFileInfo>
     */
    protected function getFiles(string $src, string $namespace, array $exclude = [], array $tagsToIgnore = [], array $excludePath = []): iterable
    {
        $files = [];
        foreach ($this->findFiles($src, $exclude, $excludePath) as $file) {
            $className = $this->getFQDNFromFile($file, $src, $namespace);

            if ($doc = (new \ReflectionClass($className))->getDocComment()) {
                // Class has tags to ignore or should be excluded
                foreach ($tagsToIgnore as $tagToIgnore) {
                    if (str_contains($doc, $tagToIgnore)) {
                        continue 2;
                    }
                }
            }

            $files[$className] = $file;
        }

        return $files;
    }

    private function findFiles(string $src, array $exclude = [], array $excludePath = []): Finder
    {
        return (new Finder())->files()
            ->in($src)
            ->name('*.php')
            ->notPath($excludePath)
            ->notName($exclude)
            ->sortByName();
    }
}
