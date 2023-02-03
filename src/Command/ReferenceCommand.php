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
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;

final class ReferenceCommand extends Command
{
    use CommandTrait;

    public function __construct(
        private readonly ConfigurationHandler $configuration,
        private readonly Environment $environment,
        private readonly string $templatePath
    ) {
        parent::__construct(name: 'reference');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates a reference documentation for a PHP class')
            ->addArgument(name: 'filename', mode: InputArgument::REQUIRED)
            ->addArgument(
                name: 'output',
                mode: InputArgument::OPTIONAL,
                description: 'The path to the file where the reference will be printed. Leave empty for screen printing'
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
        if (!$file->isFile()) {
            throw new RuntimeException(sprintf('File "%s" does not exist.', $file->getPathname()));
        }

        $reflectionClass = new ReflectionClass($this->getNamespace($file));

        $style = new SymfonyStyle($input, $output);

        $templateFileName = 'reference.*.twig';
        $templateContext = ['class' => new ClassParser($reflectionClass)];

        if ($reflectionClass->implementsInterface(ConfigurationInterface::class)) {
            $yaml = (new YamlReferenceDumper())->dump($reflectionClass->newInstance());
            if (!$yaml) {
                $style->getErrorStyle()->error(sprintf('No configuration available in "%s".', $file->getPathname()));

                return self::FAILURE;
            }

            $templateFileName = 'configuration.*.twig';
            $templateContext['configuration'] = $yaml;
        }

        $content = $this->environment->render(
            $this->getTemplateFile($input->getOption('template-path'), $templateFileName)->getFilename(),
            $templateContext
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

        return self::SUCCESS;
    }
}
