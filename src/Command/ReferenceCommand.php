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
use PhpDocumentGenerator\Link\LinkContext;
use PhpDocumentGenerator\Reflection\ReflectionClass;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;

final class ReferenceCommand extends Command
{
    use CommandTrait;

    public function __construct(
        private readonly Configuration $configuration,
    ) {
        parent::__construct(name: 'reference');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates a reference documentation for a PHP class')
            ->addArgument(name: 'filename', mode: InputArgument::REQUIRED)
            ->addOption(
                name: 'output',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The path to the file where the reference will be printed. Defaults to stdout.'
            )
            ->addOption(
                name: 'template',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The path to the template file to use to generate the reference.',
                default: Path::normalize(__DIR__.'/../../template/references/reference.php')
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
            )
            ->addOption(
                name: 'src',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The source directory.',
                default: $this->configuration->references->src
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $file = new \SplFileInfo($input->getArgument('filename'));
        if (!$file->isFile()) {
            $style->getErrorStyle()->error(sprintf('File "%s" does not exist.', $file->getPathname()));

            return self::INVALID;
        }

        $root = Path::makeAbsolute($input->getOption('src'), getcwd());
        $linkContext = new LinkContext(namespace: $input->getOption('namespace'), root: $root, baseUrl: $input->getOption('base-url'));
        $reflectionClass = new ReflectionClass($this->getFQDNFromFile($file, $input->getOption('src'), $input->getOption('namespace')), $linkContext);

        if ($reflectionClass->implementsInterface(ConfigurationInterface::class)) {
            $yaml = (new YamlReferenceDumper())->dump($reflectionClass->newInstance());
            if (!$yaml) {
                $style->getErrorStyle()->error(sprintf('No configuration available in "%s".', $file->getPathname()));

                return self::INVALID;
            }

            $out = $input->getOption('output');
            $mdx = <<<MDX
===
type: reference
php-type: Class
===

# Configuration

```yaml
{$yaml}
```

MDX;

            return $this->output($mdx, $style, $input);
        }

        $template = include $input->getOption('template');
        $content = $template($reflectionClass);

        return $this->output($content, $style, $input);
    }

    private function output(string $content, SymfonyStyle $style, InputInterface $input): int
    {
        $out = $input->getOption('output');
        if (!$out) {
            $style->write($content);

            return self::SUCCESS;
        }

        $dirName = pathinfo($out, \PATHINFO_DIRNAME);
        if (!is_dir($dirName)) {
            mkdir($dirName, 0755, true);
        }
        if (!file_put_contents($out, $content)) {
            $style->getErrorStyle()->error(sprintf('Cannot write in "%s".', $out));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
