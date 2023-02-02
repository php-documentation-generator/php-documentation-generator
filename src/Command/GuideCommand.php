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
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;

final class GuideCommand extends Command
{
    use CommandTrait;

    // Regular expression to match comment
    private const REGEX = '/^\s*\/\/\s/';

    public function __construct(
        private readonly ConfigurationHandler $configuration,
        private readonly Environment $environment,
        private readonly string $templatePath
    ) {
        parent::__construct(name: 'guide');
    }

    protected function configure(): void
    {
        $this
            ->setDescription(description: 'Creates a markdown guide based on a PHP code')
            ->addArgument(name: 'filename', mode: InputArgument::REQUIRED)
            ->addArgument(
                name: 'output',
                mode: InputArgument::OPTIONAL,
                description: 'The path to the file where the guide will be printed. Leave empty for screen printing'
            )
            ->addOption(
                name: 'template-path',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The path to the template files to use to generate the output file',
                default: $this->templatePath
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = new SplFileInfo($input->getArgument('filename'));

        $style = new SymfonyStyle($input, $output);

        $handle = fopen($file->getPathName(), 'r');
        if (!$handle) {
            $style->getErrorStyle()->error(sprintf('Error opening "%s".', $file->getPathName()));

            return self::INVALID;
        }

        // Let's split the code between an array of code and an array of text
        $sections = [];
        $linesOfCode = $linesOfText = $currentSection = 0;
        $sections[$currentSection] = ['text' => [], 'code' => []];
        // splits namespaces if found
        $namespaceOpen = false;
        // We need to put front matter headers at the start of the markdown file
        $frontMatterOpen = false;
        // we keep the headers as-is in this array
        $headers = [];

        $style->info(sprintf('Creating guide "%s".', $file->getPathName()));

        while (($line = fgets($handle)) !== false) {
            if (!isset($sections[$currentSection]['text'])) {
                $sections[$currentSection] = ['text' => [], 'code' => []];
            }

            if (!trim($line)) {
                continue;
            }

            // This is a line of text
            if (preg_match(self::REGEX, $line)) {
                $text = preg_replace(self::REGEX, '', $line);
                if ('---' === trim($text)) {
                    $frontMatterOpen = !$frontMatterOpen;
                    $headers[] = $text;
                    continue;
                }

                if ($frontMatterOpen) {
                    $headers[] = $text;
                    continue;
                }

                if (!trim($text)) {
                    continue;
                }

                if ($linesOfCode && ($linesOfText || $currentSection > 0)) {
                    ++$currentSection;
                    $sections[$currentSection] = ['text' => [], 'code' => []];
                    $linesOfCode = $linesOfText = 0;
                }

                $sections[$currentSection]['text'][] = $text;
                ++$linesOfText;

                continue;
            }

            if (false !== preg_match('/namespace (.+) \{$/', $line, $matches) && $matches) {
                $line = str_replace(' {', ';', $line);
                $namespaceOpen = true;
            } elseif ($namespaceOpen) {
                if ($line === '}'.\PHP_EOL) {
                    $line = \PHP_EOL;
                    $namespaceOpen = false;
                } else {
                    $line = substr($line, 4);
                }
            }

            if ($matches) {
                // todo "src" should be from configuration?
                $sections[$currentSection]['code'][] = '// src'.\DIRECTORY_SEPARATOR.str_replace('\\', \DIRECTORY_SEPARATOR, $matches[1]).'.php'.\PHP_EOL;
            }

            $sections[$currentSection]['code'][] = $line;
            ++$linesOfCode;

            if ($linesOfText && $linesOfCode >= $linesOfText) {
                ++$currentSection;
                $linesOfCode = $linesOfText = 0;
            }
        }

        fclose($handle);

        $content = $this->environment->render(
            $this->getTemplateFile($input->getOption('template-path'), 'guide.*.twig')->getFilename(),
            ['headers' => $headers, 'sections' => $sections]
        );

        $out = $input->getArgument('output');
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

        $style->success(sprintf('Guide "%s" successfully created.', $file->getPathname()));

        return self::SUCCESS;
    }
}
