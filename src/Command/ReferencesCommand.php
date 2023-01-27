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

namespace ApiPlatform\PDGBundle\Command;

use ApiPlatform\PDGBundle\Services\Reference\PhpDocHelper;
use ApiPlatform\PDGBundle\Services\Reference\Reflection\ReflectionHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

#[AsCommand(name: 'pdg:references')]
class ReferencesCommand extends Command
{
    public function __construct(
        private readonly PhpDocHelper $phpDocHelper,
        private readonly ReflectionHelper $reflectionHelper,
        private readonly array $patterns = [],
        private readonly string $referencePath = '',
        private readonly string $root = '',
        private readonly string $namespace = '',
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $tagsToIgnore = $this->patterns['class_tags_to_ignore'];
        $filesToExclude = $this->patterns['exclude'];

        $files = [];
        $files = $this->findFilesByName($this->patterns['names'], $files, $filesToExclude);
        $files = $this->findFilesByDirectories($this->patterns['directories'], $files, $filesToExclude);
        $files = array_unique($files);

        $namespaces = [];

        foreach ($files as $file) {
            $relativeToSrc = Path::makeRelative($file->getPath(), $this->root);

            $namespace = sprintf('%s\\%s', $this->namespace, str_replace(['/', '.php'], ['\\', ''], $relativeToSrc));
            $className = sprintf('%s\\%s', $namespace, $file->getBasename('.php'));

            $refl = new \ReflectionClass($className);

            if (!($namespaces[$namespace] ?? false)) {
                $namespaces[$namespace] = [];
            }

            $namespaces[$namespace][] = [
                'className' => $className,
                'shortName' => $file->getBasename('.php'),
                'type' => $this->getClassType($refl),
                'link' => '/reference/'.($relativeToSrc.'/'.$file->getBaseName('.php')),
            ];

            foreach ($tagsToIgnore as $tagToIgnore) {
                if ($this->phpDocHelper->classDocContainsTag($refl, $tagToIgnore)) {
                    continue 2;
                }
            }

            if ($this->reflectionHelper->containsOnlyPrivateMethods($refl)) {
                continue;
            }

            if (!@mkdir($concurrentDirectory = $this->referencePath.'/'.$relativeToSrc, 0777, true) && !is_dir($concurrentDirectory)) {
                $style->error(sprintf('Directory "%s" was not created', $concurrentDirectory));

                return Command::FAILURE;
            }

            $generateRefCommand = $this->getApplication()?->find('pdg:reference');

            $arguments = [
                'filename' => str_replace($this->namespace.'\\', '', $className).'.php',
                'output' => sprintf('%s%s%s%2$s%s.mdx', $this->referencePath, \DIRECTORY_SEPARATOR, $relativeToSrc, $file->getBaseName('.php')),
            ];

            $commandInput = new ArrayInput($arguments);

            if (Command::FAILURE === $generateRefCommand->run($commandInput, $output)) {
                $style->error(sprintf('Failed generating reference for %s', $file->getBaseNme()));

                return Command::FAILURE;
            }
        }

        // Creating an index like https://angular.io/api
        $content = '';
        foreach ($namespaces as $namespace => $classes) {
            $content .= '<article class="api-list-container">'.\PHP_EOL;
            $content .= '## '.$namespace.\PHP_EOL;
            $content .= '<ul class="api-list">'.\PHP_EOL;
            foreach ($classes as $classObj) {
                $content .= sprintf('<li class="api-item"><a href="%s"><span class="symbol %s">%2$s</span>%s</a></li>%s', $classObj['link'], $classObj['type'], $classObj['shortName'], \PHP_EOL);
            }
            $content .= '</ul>'.\PHP_EOL;
            $content .= '</article>'.\PHP_EOL;
        }

        fwrite(\STDOUT, $content);

        return Command::SUCCESS;
    }

    private function findFilesByDirectories(array $directories, array $files, array $filesToExclude = []): array
    {
        foreach ($directories as $pattern) {
            foreach ((new Finder())->files()->in($this->root.'/'.$pattern)->name('*.php')->notName($filesToExclude) as $file) {
                $files[] = $file;
            }
        }

        return $files;
    }

    private function findFilesByName(array $names, array $files, array $filesToExclude = []): array
    {
        foreach ((new Finder())->files()->in($this->root)->name($names)->notName($filesToExclude) as $file) {
            $files[] = $file;
        }

        return $files;
    }

    private function getClassType(\ReflectionClass $refl): string
    {
        if ($refl->isInterface()) {
            return 'I';
        }

        if (\count($refl->getAttributes('Attribute'))) {
            return 'A';
        }

        if ($refl->isTrait()) {
            return 'T';
        }

        return 'C';
    }
}
