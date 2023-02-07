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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Twig\Environment;

final class IndexCommand extends Command
{
    use CommandTrait;

    public function __construct(
        private readonly ConfigurationHandler $configuration,
        private readonly Environment $environment,
        private readonly string $defaultTemplate
    ) {
        parent::__construct(name: 'index');
    }

    protected function configure(): void
    {
        $this
            ->setDescription(description: 'Creates an index based on a directory of files.')
            ->addOption(
                name: 'output',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The path to the file where the index will be printed. Leave empty for screen printing'
            )
            ->addOption(
                name: 'template',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The path to the template file to use to generate the index',
                default: $this->defaultTemplate
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $out = $input->getOption('output');

        $sections = [];
        $directories = [
            'guides' => $this->configuration->get('guides.output'),
            'references' => $this->configuration->get('references.output'),
        ];
        foreach ($directories as $section => $directory) {
            foreach ((new Finder())->files()->in($directory)->sortByName() as $file) {
                // Ignore indexes
                if ('index' === $file->getFilenameWithoutExtension()) {
                    continue;
                }

                $path = Path::makeRelative($file->getPathName(), $directory);
                $subDirectory = null;

                if (1 < \count($parts = explode(\DIRECTORY_SEPARATOR, $path))) {
                    array_pop($parts);
                    $subDirectory = implode('\\', $parts);
                    if (!isset($sections[$section][$subDirectory])) {
                        $sections[$section][$subDirectory] = [];
                    }
                }

                $sections[$section][$subDirectory][] = $file->getPathname();
            }
        }

        $content = $this->environment->render($this->loadTemplate($input->getOption('template')), $sections);

        if (!$out) {
            $style->block($content);

            return self::SUCCESS;
        }

        $dirName = pathinfo($out, \PATHINFO_DIRNAME);
        if (!is_dir($dirName)) {
            mkdir($dirName, 0777, true);
        }
        if (!file_put_contents($out, $content)) {
            $style->getErrorStyle()->error(sprintf('Cannot write in "%s".', $out));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
