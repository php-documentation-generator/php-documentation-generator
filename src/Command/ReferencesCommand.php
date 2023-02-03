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

use PhpDocumentGenerator\Services\ConfigurationHandler;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Twig\Environment;

final class ReferencesCommand extends AbstractReferencesCommand
{
    use CommandTrait;

    public function __construct(
        private readonly ConfigurationHandler $configuration,
        private readonly Environment $environment,
        private readonly string $templatePath
    ) {
        parent::__construct($configuration, name: 'references');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates references documentation for PHP classes')
            ->addArgument(
                name: 'output',
                mode: InputArgument::REQUIRED,
                description: 'The path where the references will be printed'
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
        $templatePath = $input->getOption('template-path');
        $out = $input->getArgument('output');

        // get the output extension for a reference
        $referenceExtension = pathinfo($this->getTemplateFile($templatePath, 'reference.*.twig')->getBasename('.twig'), \PATHINFO_EXTENSION);

        $style = new SymfonyStyle($input, $output);
        $style->progressStart();

        foreach ($this->getFiles() as $file) {
            $relativeToSrc = Path::makeRelative($file->getPath(), $this->configuration->get('reference.src'));

            if (!@mkdir($concurrentDirectory = $this->configuration->get('target.directories.reference_path').\DIRECTORY_SEPARATOR.$relativeToSrc, 0777, true) && !is_dir($concurrentDirectory)) {
                $style->getErrorStyle()->error(sprintf('Cannot create directory "%s".', $concurrentDirectory));

                return self::FAILURE;
            }

            // run "reference" command
            if (
                self::FAILURE === $this->getApplication()?->find('reference')->run(new ArrayInput([
                    'filename' => $file->getPathName(),
                    '--template-path' => $templatePath,
                    '--output' => sprintf('%s%s%s%2$s%s.%s', rtrim($out, \DIRECTORY_SEPARATOR), \DIRECTORY_SEPARATOR, $relativeToSrc, $file->getBaseName('.php'), $referenceExtension),
                ]), $output)
            ) {
                $style->getErrorStyle()->error(sprintf('Failed creating reference "%s".', $file->getPathname()));

                return self::FAILURE;
            }

            $style->progressAdvance();
        }

        $style->progressFinish();

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
