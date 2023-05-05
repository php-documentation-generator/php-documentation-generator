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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;

final class GuideCommand extends Command
{
    use CommandTrait;

    // Regular expression to match comment
    private const REGEX = '/^\s*\/\/\s/';

    public function __construct()
    {
        parent::__construct(name: 'guide');
    }

    protected function configure(): void
    {
        $this
            ->setDescription(description: 'Creates a guide based on a PHP code')
            ->addArgument(name: 'filename', mode: InputArgument::REQUIRED)
            ->addOption(
                name: 'output',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The path to the file where the reference will be printed. Defaults to stdout.'
            )
            ->addOption(
                name: 'template',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The path to the template files to use to generate the output file.',
                default: Path::normalize(__DIR__.'/../../template/guides/guide.php')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $stderr = $style->getErrorStyle();

        $file = new \SplFileInfo($input->getArgument('filename'));
        if (!$file->isFile()) {
            $stderr->error(sprintf('File "%s" does not exist.', $file->getPathname()));

            return self::INVALID;
        }

        $handle = fopen($file->getPathName(), 'r');
        if (!$handle) {
            $stderr->error(sprintf('Error opening "%s".', $file->getPathName()));

            return self::INVALID;
        }

        // Let's split the code between an array of code and an array of text
        $sections = [];
        $currentSection = 0;
        $sections[$currentSection] = ['text' => [], 'code' => []];
        // splits namespaces if found
        $namespaceOpen = false;
        // We need to put front matter headers at the start of the markdown file
        $frontMatterOpen = false;
        // we keep the headers as-is in this array
        $headers = [];

        $previousLine = 'text';
        $isPhp = false;

        while (($line = fgets($handle)) !== false) {
            if (!trim($line)) {
                continue;
            }

            if ('<?php' === trim($line)) {
                $isPhp = true;
                continue;
            }

            if (!isset($sections[$currentSection]['text'])) {
                $sections[$currentSection] = ['text' => [], 'code' => []];
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

                if ('code' === $previousLine) {
                    ++$currentSection;
                    $sections[$currentSection] = ['text' => [], 'code' => []];
                }
                $sections[$currentSection]['text'][] = $text;

                $previousLine = 'text';

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
                if ($sections[$currentSection]['code']) {
                    $sections[$currentSection]['code'][] = \PHP_EOL;
                }
                $sections[$currentSection]['code'][] = sprintf(
                    '// src%s%s.php%s',
                    \DIRECTORY_SEPARATOR,
                    str_replace('\\', \DIRECTORY_SEPARATOR, $matches[1]),
                    \PHP_EOL
                );
            }

            if (!trim($line)) {
                continue;
            }

            $sections[$currentSection]['code'][] = $line;
            $previousLine = 'code';
        }

        fclose($handle);

        if (!$isPhp) {
            $stderr->error(sprintf('Guide "%s" is not a PHP file.', $file->getPathname()));

            return self::INVALID;
        }

        $template = include $input->getOption('template');
        $content = $template($headers, $sections);

        // Or with twig:
        // $content = $this->environment->render(
        //     $this->loadTemplate($input->getOption('template')),
        //     ['headers' => $headers, 'sections' => $sections]
        // );

        $out = $input->getOption('output');
        if (!$out) {
            $output->write($content);

            if (!$input->getOption('quiet')) {
                $stderr->success(sprintf('Guide "%s" successfully created.', $file->getPathname()));
            }

            return self::SUCCESS;
        }

        $dirName = pathinfo($out, \PATHINFO_DIRNAME);
        if (!is_dir($dirName)) {
            mkdir($dirName, 0755, true);
        }
        if (!file_put_contents($out, $content)) {
            $stderr->error(sprintf('Cannot write in "%s".', $out));

            return self::FAILURE;
        }

        if (!$input->getOption('quiet')) {
            $stderr->success(sprintf('Guide "%s" successfully created.', $file->getPathname()));
        }

        return self::SUCCESS;
    }
}
