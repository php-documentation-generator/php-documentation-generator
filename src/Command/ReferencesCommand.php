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

final class ReferencesCommand extends AbstractReferencesCommand
{
    public function __construct(
        private readonly ConfigurationHandler $configuration,
        private readonly string $defaultTemplate
    ) {
        parent::__construct($configuration, name: 'references');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates references documentation for PHP classes')
            ->addArgument(
                name: 'output',
                mode: InputArgument::OPTIONAL,
                description: 'The path where the references will be printed'
            )
            ->addOption(
                name: 'template',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The path to the template file to use to generate each reference',
                default: $this->defaultTemplate
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = $this->getFiles();
        $style = new SymfonyStyle($input, $output);

        if (!\count($files)) {
            $style->getErrorStyle()->warning(sprintf('No files were found in "%s".', $this->configuration->get('references.src')));

            return self::INVALID;
        }

        $template = $input->getOption('template');
        $out = $input->getArgument('output') ?: $this->configuration->get('references.output');

        // get the output extension for a reference
        $referenceExtension = pathinfo(preg_replace('/\.twig$/i', '', $template), \PATHINFO_EXTENSION);

        $style->progressStart();
        foreach ($files as $file) {
            $relativeToSrc = Path::makeRelative($file->getPath(), $this->configuration->get('references.src'));

            if (!@mkdir($concurrentDirectory = $this->configuration->get('references.output').\DIRECTORY_SEPARATOR.$relativeToSrc, 0777, true) && !is_dir($concurrentDirectory)) {
                $style->getErrorStyle()->error(sprintf('Cannot create directory "%s".', $concurrentDirectory));

                return self::FAILURE;
            }

            // run "reference" command
            if (
                self::FAILURE === $this->getApplication()?->find('reference')->run(new ArrayInput([
                    'filename' => $file->getPathName(),
                    '--template' => $template,
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
}
