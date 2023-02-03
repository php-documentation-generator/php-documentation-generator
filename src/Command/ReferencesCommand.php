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

use ApiPlatform\PDGBundle\Parser\ClassParser;
use ApiPlatform\PDGBundle\Services\ConfigurationHandler;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Twig\Environment;

final class ReferencesCommand extends Command
{
    use CommandTrait;

    public function __construct(
        private readonly ConfigurationHandler $configuration,
        private readonly Environment $environment,
        private readonly string $templatePath
    ) {
        parent::__construct(name: 'references');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates references documentation for PHP classes')
            ->addArgument(
                name: 'output',
                mode: InputArgument::OPTIONAL,
                description: 'The path where the references will be printed. Leave empty for screen printing'
            )
            ->addOption(
                name: 'template-path',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The path to the template files to use to create each reference output file',
                default: $this->templatePath
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $patterns = $this->configuration->get('reference.patterns');
        $tagsToIgnore = $patterns['class_tags_to_ignore'] ?? ['@internal', '@experimental'];
        $files = $this->findFiles($patterns['directories'] ?? [], $patterns['names'] ?? ['*.php'], $patterns['exclude'] ?? []);
        $namespaces = [];

        $templatePath = $input->getOption('template-path');
        $outputPath = $input->getArgument('output');

        // get the output extension for a reference
        $referenceExtension = pathinfo($this->getTemplateFile($templatePath, 'reference.*.twig')->getBasename('.twig'), \PATHINFO_EXTENSION);

        $style = new SymfonyStyle($input, $output);
        $style->progressStart(\count($files));

        foreach ($files as $file) {
            $relativeToSrc = Path::makeRelative($file->getPath(), $this->configuration->get('reference.src'));
            $namespace = rtrim(sprintf('%s\\%s', $this->configuration->get('reference.namespace'), str_replace([\DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relativeToSrc)), '\\');

            try {
                $reflectionClass = new ClassParser(new ReflectionClass(sprintf('%s\\%s', $namespace, $file->getBasename('.php'))));
            } catch (\ReflectionException) {
                $style->getErrorStyle()->error(sprintf('File "%s" does not seem to be a valid PHP class.', $file->getPathname()));

                return self::FAILURE;
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

            // run "reference" command
            $fileOutputPath = $outputPath;
            if ($fileOutputPath) {
                $fileOutputPath = sprintf('%s%s%s%2$s%s.%s', rtrim($fileOutputPath, \DIRECTORY_SEPARATOR), \DIRECTORY_SEPARATOR, $relativeToSrc, $file->getBaseName('.php'), $referenceExtension);

                if (!@mkdir($concurrentDirectory = $this->configuration->get('target.directories.reference_path').\DIRECTORY_SEPARATOR.$relativeToSrc, 0777, true) && !is_dir($concurrentDirectory)) {
                    $style->getErrorStyle()->error(sprintf('Cannot create directory "%s".', $concurrentDirectory));

                    return self::FAILURE;
                }
            }
            if (
                self::FAILURE === $this->getApplication()?->find('reference')->run(new ArrayInput([
                    'filename' => $file->getPathName(),
                    'output' => $fileOutputPath,
                    '--template-path' => $templatePath,
                ]), $output)
            ) {
                $style->getErrorStyle()->error(sprintf('Failed creating reference "%s".', $file->getPathname()));

                return self::FAILURE;
            }

            $namespaces[$namespace][] = $reflectionClass;

            $style->progressAdvance();
        }

        $style->progressFinish();

        // Creating an index like https://angular.io/api
        $templateFile = $this->getTemplateFile($templatePath, 'index.*.twig');
        $content = $this->environment->render($templateFile->getFilename(), ['namespaces' => $namespaces]);
        if (!$outputPath) {
            $style->block($content);

            return self::SUCCESS;
        }

        $indexExtension = pathinfo($templateFile->getBasename('.twig'), \PATHINFO_EXTENSION);
        $fileName = sprintf('%s%sindex.%s', rtrim($outputPath, \DIRECTORY_SEPARATOR), \DIRECTORY_SEPARATOR, $indexExtension);
        $dirName = pathinfo($fileName, \PATHINFO_DIRNAME);
        if (!is_dir($dirName)) {
            mkdir($dirName, 0777, true);
        }
        if (!file_put_contents($fileName, $content)) {
            $style->getErrorStyle()->error(sprintf('Cannot write in "%s".', $fileName));

            return self::FAILURE;
        }

        $style->success('References index successfully created.');

        return self::SUCCESS;
    }

    private function findFiles(array $directories, array $names, array $exclude): Finder
    {
        return (new Finder())->files()
            ->in(array_map(fn (string $directory) => $this->configuration->get('reference.src').\DIRECTORY_SEPARATOR.$directory, $directories))
            ->name($names)
            ->notName($exclude);
    }
}
