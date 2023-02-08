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
use PhpDocumentGenerator\Parser\ClassParser;
use ReflectionClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;
use Twig\Environment;

final class ReferencesIndexCommand extends AbstractReferencesCommand
{
    use CommandTrait;

    public function __construct(
        private readonly Configuration $configuration,
        Environment $environment,
        private readonly string $defaultTemplate
    ) {
        $this->environment = $environment;
        parent::__construct(name: 'references:index');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates references index')
            ->addOption(
                name: 'output',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The path to the file where the index will be printed. Defaults to stdout.'
            )
            ->addOption(
                name: 'template',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The path to the template file to use to generate the index.',
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
            ->addArgument(
                name: 'src',
                description: 'The source directory',
                default: $this->configuration->references->src
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $namespaces = [];
        $style = new SymfonyStyle($input, $output);
        $src = Path::canonicalize($input->getArgument('src'));

        foreach ($this->getFiles($src, $input->getOption('namespace'), (array) $input->getOption('exclude'), (array) $input->getOption('tags-to-ignore'), (array) $input->getOption('exclude-path')) as $class => $file) {
            $class = new ClassParser(new ReflectionClass($class));
            $namespaces[$class->getNamespaceName()][] = $class;
        }

        // Creating an index like https://angular.io/api
        $content = $this->environment->render(
            $this->loadTemplate($input->getOption('template')),
            ['namespaces' => $namespaces]
        );

        $out = $input->getOption('output');
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
