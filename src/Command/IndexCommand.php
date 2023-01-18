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

namespace PDG\Command;

use Spatie\YamlFrontMatter\YamlFrontMatter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Exception\ParseException;

// the name of the command is what users type after "php bin/console"
#[AsCommand(name: 'pdg:index')]
class IndexCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setDescription('Creates an index based on a directory of markdown files.')
            ->addArgument(
                name: 'directory',
                mode: InputArgument::IS_ARRAY,
                description: 'PHP file to make the guide of.',
                default: ['pages/guide', 'pages/reference']
            )
            ->addOption(
                name: 'basePath',
                default: 'pages',
                mode: InputOption::VALUE_OPTIONAL
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $stderr = $io->getErrorStyle();

        $glob = '*.mdx';
        $directories = ['Guides' => ['pages/guide'], 'Reference' => ['pages/reference']];
        foreach ($directories as $title => $directories) {
            $output->writeln('## '.$title);
            $namespaces = ['' => []];

            foreach ((new Finder())->files()->in($directories)->sortByName() as $file) {
                $path = Path::makeRelative($file->getPathName(), $input->getOption('basePath'));
                $parts = explode(\DIRECTORY_SEPARATOR, $path);
                $n = \count($parts);
                $namespace = '';
                $basename = basename($path, '.'.$file->getExtension());

                $object = null;
                try {
                    $object = YamlFrontMatter::parse(file_get_contents($file->getPathName()));
                } catch (ParseException $e) {
                }

                if ($matter = $object?->matter()) {
                    $prettyName = $matter['name'] ?? str_replace('-', ' ', $basename);
                    $namespaces[$namespace][] = sprintf('- [%s](/%s/%s)', $prettyName, Path::getDirectory($path), $matter['slug'] ?? $basename);

                    if (isset($matter['slug'])) {
                        rename($file->getPathName(), str_replace(basename($path), $matter['slug'].'.'.$file->getExtension(), $file->getPathName()));
                    }
                    continue;
                }

                // This is a reference
                if ($n > 2) {
                    array_shift($parts);
                    array_pop($parts);
                    // array_unshift($parts, "ApiPlaform");
                    $namespace = implode('\\', $parts);
                    if (!isset($namespaces[$namespace])) {
                        $namespaces[$namespace] = [];
                    }
                }

                if (false !== preg_match('/^\d+\-/', $basename, $matches) && $matches) {
                    $basename = str_replace($matches[0], '', $basename);
                }
                $prettyName = str_replace('-', ' ', $basename);
                $namespaces[$namespace][] = sprintf('- [%s](/%s/%s)', $prettyName, Path::getDirectory($path), $basename);
            }

            foreach ($namespaces as $namespace => $files) {
                if ($namespace) {
                    $output->writeln('### '.$namespace);
                }

                $output->writeln(implode(\PHP_EOL, $files));
            }
        }

        return Command::SUCCESS;
    }
}
