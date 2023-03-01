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

use PhpDocumentGenerator\Configuration;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\SplFileInfo;

final class ReferencesCommand extends AbstractReferencesCommand
{
    public function __construct(
        private readonly Configuration $configuration,
        private readonly string $defaultTemplate
    ) {
        parent::__construct(name: 'references');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates references documentation for PHP classes')
            ->addArgument(
                name: 'output',
                description: 'The path where the references will be printed.',
                default: $this->configuration->references->output
            )
            ->addArgument(
                name: 'src',
                description: 'The source directory',
                default: $this->configuration->references->src
            )
            ->addOption(
                name: 'template',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The path to the template file to use to generate each reference.',
                default: $this->defaultTemplate
            )
            ->addOption(
                name: 'exclude',
                mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                description: 'Glob patterns to exclude.',
                default: $this->configuration->references->exclude
            )
            ->addOption(
                name: 'exclude-path',
                mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                description: 'Paths to exclude.',
                default: $this->configuration->references->excludePath
            )
            ->addOption(
                name: 'tags-to-ignore',
                mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                description: 'The tags to ignore.',
                default: $this->configuration->references->tagsToIgnore
            )
            ->addOption(
                name: 'namespace',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The PSR-4 prefix representing your source directory.',
                default: $this->configuration->references->namespace
            )
            ->addOption(
                name: 'base-url',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The base URL for references.',
                default: $this->configuration->references->baseUrl
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $stderr = $style->getErrorStyle();
        $template = $input->getOption('template');
        $baseUrl = $input->getOption('base-url');
        $namespace = $input->getOption('namespace');

        if (!$input->getArgument('src') || !$input->getArgument('output')) {
            $stderr->error('Specify "src" and "output" to create references.');

            return self::INVALID;
        }

        $src = Path::canonicalize($input->getArgument('src'));
        $out = Path::canonicalize($input->getArgument('output'));

        // get the output extension for a reference
        $referenceExtension = Path::getExtension(preg_replace('/\.twig$/i', '', $template));
        /** @var SplFileInfo[] */
        $files = $this->getFiles($src, $namespace, (array) $input->getOption('exclude'), (array) $input->getOption('tags-to-ignore'), (array) $input->getOption('exclude-path'));

        if (!\count($files)) {
            $stderr->warning(sprintf('No files were found in "%s".', $src));

            return self::INVALID;
        }

        $progressBar = $output->isDebug() ? null : $style->createProgressBar(\count($files));
        $progressBar?->start();

        foreach ($files as $file) {
            $relativeToSrc = Path::makeRelative($file->getPath(), $src);
            $target = Path::changeExtension(Path::join($out, $relativeToSrc, $file->getBaseName()), $referenceExtension);

            $stderr->writeln(sprintf('Processing %s => %s', $file, $target), OutputInterface::VERBOSITY_DEBUG);

            if (!@mkdir($concurrentDirectory = $out.\DIRECTORY_SEPARATOR.$relativeToSrc, 0755, true) && !is_dir($concurrentDirectory)) {
                $stderr->error(sprintf('Cannot create directory "%s".', $concurrentDirectory));

                return self::FAILURE;
            }

            // run "reference" command
            if (
                self::FAILURE === $this->getApplication()?->find('reference')->run(new ArrayInput([
                    'filename' => $file->getPathName(),
                    '--namespace' => $namespace,
                    '--template' => $template,
                    '--output' => $target,
                    '--base-url' => $baseUrl,
                    '--src' => $src,
                    '--quiet' => true,
                ]), $output)
            ) {
                $stderr->error(sprintf('Failed creating reference "%s".', $file->getPathname()));

                return self::FAILURE;
            }

            $progressBar?->advance(1);
        }

        $progressBar?->finish();

        return self::SUCCESS;
    }
}
