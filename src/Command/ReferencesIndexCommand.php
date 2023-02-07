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
        private readonly string $defaultTemplate
    ) {
        parent::__construct($configuration, name: 'references:index');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates references index')
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
        $namespaces = [];
        $style = new SymfonyStyle($input, $output);

        foreach ($this->getFiles() as $class => $file) {
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
