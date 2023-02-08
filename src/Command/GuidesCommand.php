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
        private readonly ConfigurationHandler $configuration,
        private readonly string $defaultTemplate
    ) {
        parent::__construct(name: 'guides');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates Guides based on PHP code')
            ->addArgument(
                name: 'output',
                mode: InputArgument::OPTIONAL,
                description: 'The path where the guides will be printed'
            )
            ->addOption(
                name: 'template',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The path to the template file to use to generate each guide',
                default: $this->defaultTemplate
            )
            ->addOption(
                name: 'directory',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'The path to the directory that contains the guides',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $guideDir = $input->getOption('directory') ?? $this->configuration->get('guides.src');
        $files = (new Finder())->files()->in($guideDir)->sortByName();
        $style = new SymfonyStyle($input, $output);

        if (!\count($files)) {
            $style->getErrorStyle()->warning(sprintf('No files were found in "%s".', $guideDir));

            return self::INVALID;
        }

        $template = $input->getOption('template');
        $out = $input->getArgument('output') ?: $this->configuration->get('guides.output');

        $guideExtension = Path::getExtension(preg_replace('/\.twig$/i', '', $template));

        foreach ($files as $file) {
            $input = new ArrayInput([
                'filename' => $file->getPathName(),
                '--output' => Path::changeExtension(sprintf('%s%s%s', rtrim($out, \DIRECTORY_SEPARATOR), \DIRECTORY_SEPARATOR, $file->getBaseName()), $guideExtension),
                '--template' => $template,
            ]);
            if (self::FAILURE === $this->getApplication()?->find('guide')->run($input, $output)) {
                $style->getErrorStyle()->error(sprintf('Failed creating guide "%s".', $file->getPathname()));

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
