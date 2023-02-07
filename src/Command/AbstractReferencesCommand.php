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

use Generator;
use PhpDocumentGenerator\Parser\ClassParser;
use PhpDocumentGenerator\Services\ConfigurationHandler;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

abstract class AbstractReferencesCommand extends Command
{
    public function __construct(private readonly ConfigurationHandler $configuration, ?string $name = null)
    {
        parent::__construct($name);
    }

    /**
     * @return Generator<string, SplFileInfo>
     */
    protected function getFiles(): iterable
    {
        $patterns = $this->configuration->get('references.patterns');
        $tagsToIgnore = $patterns['class_tags_to_ignore'] ?? ['@internal', '@experimental'];
        $files = $this->findFiles($patterns['directories'] ?? [], $patterns['names'] ?? ['*.php'], $patterns['exclude'] ?? []);

        foreach ($files as $file) {
            $relativeToSrc = Path::makeRelative($file->getPath(), $this->configuration->get('references.src'));
            $namespace = rtrim(sprintf('%s\\%s', $this->configuration->get('references.namespace'), str_replace([\DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relativeToSrc)), '\\');
            $className = sprintf('%s\\%s', $namespace, $file->getBasename('.php'));

            try {
                $reflectionClass = new ClassParser(new ReflectionClass($className));
            } catch (\ReflectionException) {
                throw new RuntimeException(sprintf('File "%s" does not seem to be a valid PHP class.', $file->getPathname()));
            }

            foreach ($tagsToIgnore as $tagToIgnore) {
                if ($reflectionClass->hasTag($tagToIgnore)) {
                    continue 2;
                }
            }

            // class is not an interface nor a trait, and has no protected/public methods nor properties
            if (
                !$reflectionClass->isTrait()
                && !$reflectionClass->isInterface()
                && !\count($reflectionClass->getMethods())
                && !\count($reflectionClass->getProperties())
            ) {
                continue;
            }

            yield $className => $file;
        }
    }

    private function findFiles(array $directories, array $names, array $exclude): Finder
    {
        return (new Finder())->files()
            ->in(array_map(fn (string $directory) => $this->configuration->get('references.src').\DIRECTORY_SEPARATOR.$directory, $directories))
            ->name($names)
            ->notName($exclude);
    }
}
