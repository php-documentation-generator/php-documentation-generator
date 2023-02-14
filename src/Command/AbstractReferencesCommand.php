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

namespace PhpDocumentGenerator\Command;

use PhpDocumentGenerator\Parser\ClassParser;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

abstract class AbstractReferencesCommand extends Command
{
    use CommandTrait;

    public function __construct(?string $name = null)
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

            try {
                $reflectionClass = new ReflectionClass($className);
                $classParser = new ClassParser($reflectionClass);
            } catch (\ReflectionException) {
                throw new RuntimeException(sprintf('File "%s" does not seem to be a valid PHP class.', $file->getPathname()));
            }

            // Class has tags to ignore or should be excluded
            foreach ($tagsToIgnore as $tagToIgnore) {
                if ($classParser->hasTag($tagToIgnore)) {
                    continue 2;
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
