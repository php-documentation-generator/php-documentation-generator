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

use PhpDocumentGenerator\Configuration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

final class GuidesCommand extends Command
{
    public function __construct(
        private readonly Configuration $configuration
    ) {
        parent::__construct(name: 'guides');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates Guides based on PHP code.')
            ->addArgument(
                name: 'output',
                mode: InputArgument::OPTIONAL,
                description: 'The path where the guides will be printed.',
                default: $this->configuration->guides->output
            )
            ->addArgument(
                name: 'directory',
                mode: InputArgument::OPTIONAL,
                description: 'The path to the directory that contains the guides.',
                default: $this->configuration->guides->src
            )
            ->addOption(
                name: 'template',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The path to the template file to use to generate each guide.',
                default: Path::normalize(__DIR__.'/../../template/guides/guide.php')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $stderr = $style->getErrorStyle();
        $guideDir = $input->getArgument('directory');

        if (!$guideDir) {
            $stderr->error('No guides directory.');

            return self::INVALID;
        }

        $files = (new Finder())->files()->in($guideDir)->sortByName();

        if (!\count($files)) {
            $stderr->warning(sprintf('No files were found in "%s".', $guideDir));

            return self::INVALID;
        }

        $progressBar = $output->isDebug() ? null : $style->createProgressBar(\count($files));
        $progressBar?->start();

        $template = $input->getOption('template');
        $out = $input->getArgument('output');

        if (!($application = $this->getApplication())) {
            throw new \RuntimeException('No Application.');
        }

        $guideCommand = $application->find('guide');
        // TODO: add an option
        // $guideExtension = Path::getExtension(preg_replace('/\.twig$/i', '', $template));

        foreach ($files as $file) {
            $target = Path::changeExtension(Path::join($out, $file->getBaseName()), '.mdx');
            $input = new ArrayInput([
                'filename' => $file->getPathName(),
                '--output' => $target,
                '--template' => $template,
                '--quiet' => true,
            ]);

            $stderr->writeln(sprintf('Processing %s => %s', $file->getBaseName(), $target), OutputInterface::VERBOSITY_DEBUG);

            if (self::FAILURE === $guideCommand->run($input, $output)) {
                $stderr->error(sprintf('Failed creating guide "%s".', $file->getPathname()));

                return self::FAILURE;
            }

            $progressBar?->advance(1);
        }

        $progressBar?->finish();

        return self::SUCCESS;
    }
}
