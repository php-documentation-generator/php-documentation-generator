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

use ApiPlatform\PDGBundle\Services\ConfigurationHandler;
use ApiPlatform\PDGBundle\Services\Reference\PhpDocHelper;
use ApiPlatform\PDGBundle\Services\Reference\Reflection\ReflectionHelper;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

final class ReferencesCommand extends Command
{
    public function __construct(
        private readonly PhpDocHelper $phpDocHelper,
        private readonly ReflectionHelper $reflectionHelper,
        private readonly ConfigurationHandler $configuration
    ) {
        parent::__construct(name: 'references');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $patterns = $this->configuration->get('reference.patterns');
        $tagsToIgnore = $patterns['class_tags_to_ignore'] ?? ['@internal', '@experimental'];
        $filesToExclude = $patterns['exclude'] ?? [];

        $files = [];
        $files = $this->findFilesByName($patterns['names'] ?? ['*.php'], $files, $filesToExclude);
        $files = $this->findFilesByDirectories($patterns['directories'] ?? [], $files, $filesToExclude);
        $files = array_unique($files);

        $namespaces = [];

        foreach ($files as $file) {
            $relativeToSrc = Path::makeRelative($file->getPath(), $this->configuration->get('reference.src'));

            $namespace = rtrim(sprintf('%s\\%s', $this->configuration->get('reference.namespace'), str_replace(['/', '.php'], ['\\', ''], $relativeToSrc)), '\\');
            $className = sprintf('%s\\%s', $namespace, $file->getBasename('.php'));

            $refl = new ReflectionClass($className);

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

            if (!@mkdir($concurrentDirectory = $this->configuration->get('target.directories.reference_path').'/'.$relativeToSrc, 0777, true) && !is_dir($concurrentDirectory)) {
                $style->error(sprintf('Directory "%s" was not created', $concurrentDirectory));

                return Command::FAILURE;
            }

            $generateRefCommand = $this->getApplication()?->find('reference');

            $arguments = [
                'filename' => $file->getPathName(),
                'output' => sprintf('%s%s%s%2$s%s.mdx', $this->configuration->get('target.directories.reference_path'), \DIRECTORY_SEPARATOR, $relativeToSrc, $file->getBaseName('.php')),
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
            foreach ((new Finder())->files()->in($this->configuration->get('reference.src').'/'.$pattern)->name('*.php')->notName($filesToExclude) as $file) {
                $files[] = $file;
            }
        }

        return $files;
    }

    private function findFilesByName(array $names, array $files, array $filesToExclude = []): array
    {
        foreach ((new Finder())->files()->in($this->configuration->get('reference.src'))->name($names)->notName($filesToExclude) as $file) {
            $files[] = $file;
        }

        return $files;
    }

    private function getClassType(ReflectionClass $refl): string
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
