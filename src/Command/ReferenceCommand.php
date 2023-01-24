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

use ApiPlatform\PDGBundle\Services\Reference\OutputFormatter;
use ApiPlatform\PDGBundle\Services\Reference\PhpDocHelper;
use ApiPlatform\PDGBundle\Services\Reference\Reflection\ReflectionHelper;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'pdg:reference')]
class ReferenceCommand extends Command
{
    private \ReflectionClass $reflectionClass;

    public function __construct(
        private readonly PhpDocHelper $phpDocHelper,
        private readonly ReflectionHelper $reflectionHelper,
        private readonly OutputFormatter $outputFormatter,
        private string $namespace = '',
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'filename',
                InputArgument::REQUIRED
            )
            ->addArgument(
                'output',
                InputArgument::OPTIONAL,
                'The path to the mdx file where the reference will be printed.Leave empty for screen printing'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $stderr = $style->getErrorStyle();
        $fileName = $input->getArgument('filename');

        $stderr->info(sprintf('Generating reference for %s', $fileName));
        $namespace = sprintf('%s\\%s', $this->namespace, str_replace(['/', '.php'], ['\\', ''], $fileName));
        $content = '';

        $this->reflectionClass = new \ReflectionClass($namespace);
        $outputFile = $input->getArgument('output');

        if ($this->reflectionClass->implementsInterface(ConfigurationInterface::class)) {
            return $this->generateConfigExample($stderr, $outputFile);
        }

        $content = $this->outputFormatter->writePageTitle($this->reflectionClass, $content);
        $content = $this->outputFormatter->writeClassName($this->reflectionClass, $content);
        $content = $this->reflectionHelper->handleParent($this->reflectionClass, $content);
        $content = $this->reflectionHelper->handleImplementations($this->reflectionClass, $content);
        $content = $this->phpDocHelper->handleClassDoc($this->reflectionClass, $content);
        $content = $this->reflectionHelper->handleClassConstants($this->reflectionClass, $content);
        $content = $this->reflectionHelper->handleProperties($this->reflectionClass, $content);
        $content = $this->reflectionHelper->handleMethods($this->reflectionClass, $content);

        if (!$outputFile) {
            fwrite(\STDOUT, $content);
            $stderr->success('Reference successfully printed on stdout for '.$fileName);

            return Command::SUCCESS;
        }

        if (!fwrite(fopen($outputFile, 'w'), $content)) {
            $stderr->error('Error opening or writing '.$outputFile);

            return Command::FAILURE;
        }
        $stderr->success('Reference successfully generated for '.$fileName);

        return Command::SUCCESS;
    }

    private function generateConfigExample(SymfonyStyle $style, ?string $outputFile): int
    {
        $style->info('Generating configuration reference');

        $yaml = (new YamlReferenceDumper())->dump($this->reflectionClass->newInstance());
        if (!$yaml) {
            $style->error('No configuration is available');

            return Command::FAILURE;
        }

        $content = $this->outputFormatter->writePageTitle($this->reflectionClass, '');
        $content .= '# Configuration Reference'.\PHP_EOL;
        $content .= sprintf('```yaml'.\PHP_EOL.'%s```', $yaml);
        $content .= \PHP_EOL;

        if (!fwrite(fopen($outputFile, 'w'), $content)) {
            $style->error('Error opening or writing '.$outputFile);

            return Command::FAILURE;
        }
        $style->success('Configuration reference successfully generated');

        return Command::SUCCESS;
    }
}
