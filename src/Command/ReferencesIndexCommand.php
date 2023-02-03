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

use PhpDocumentGenerator\Parser\ClassParser;
use PhpDocumentGenerator\Services\ConfigurationHandler;
use ReflectionClass;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;

final class ReferencesIndexCommand extends AbstractReferencesCommand
{
    use CommandTrait;

    public function __construct(
        private readonly ConfigurationHandler $configuration,
        private readonly Environment $environment,
        private readonly string $templatePath
    ) {
        parent::__construct($configuration, name: 'references-index');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates references index')
            ->addArgument(
                name: 'output',
                mode: InputArgument::OPTIONAL,
                description: 'The path where the references will be printed. Leave empty for screen printing'
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
        $namespaces = [];
        $style = new SymfonyStyle($input, $output);

        foreach ($this->getFiles() as $class => $file) {
            $class = new ClassParser(new ReflectionClass($class));
            $namespaces[$class->getNamespaceName()][] = $class;
        }

        // Creating an index like https://angular.io/api
        $templatePath = $input->getOption('template-path');
        $outputPath = $input->getArgument('output');
        $templateFile = $this->getTemplateFile($templatePath, 'index.*.twig');
        $content = $this->environment->render($templateFile->getFilename(), ['namespaces' => $namespaces]);
        if (!$outputPath) {
            $style->block($content);

            return self::SUCCESS;
        }

        $indexExtension = pathinfo($templateFile->getBasename('.twig'), \PATHINFO_EXTENSION);
        $fileName = sprintf('%s%sindex.%s', rtrim($outputPath, \DIRECTORY_SEPARATOR), \DIRECTORY_SEPARATOR, $indexExtension);
        $dirName = pathinfo($fileName, \PATHINFO_DIRNAME);
        if (!is_dir($dirName)) {
            mkdir($dirName, 0777, true);
        }
        if (!file_put_contents($fileName, $content)) {
            $style->getErrorStyle()->error(sprintf('Cannot write in "%s".', $fileName));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
