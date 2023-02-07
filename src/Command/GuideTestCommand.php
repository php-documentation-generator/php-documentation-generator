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

use App\Kernel;
use LogicException;
use PhpDocumentGenerator\Playground\Command\PhpUnitCommand;
use PhpDocumentGenerator\Playground\PlaygroundTestCase;
use PHPUnit\Framework\TestSuite;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class GuideTestCommand extends Command
{
    public function __construct()
    {
        parent::__construct(name: 'guide:test');
    }

    protected function configure(): void
    {
        $this->addArgument(
            name: 'guide',
            mode: InputArgument::REQUIRED,
            description: 'The path to the guide to test'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // This requires the configured autoloader
        $style = new SymfonyStyle($input, $output);
        $guide = $input->getArgument('guide');
        $style->info('Testing guide: '.$guide);

        $matter = $this->getFrontMatter($guide);
        $executable = $matter['executable'] ?? false;

        $suite = new TestSuite();

        require $guide;

        $testClasses = $this->getDeclaredClassesForNamespace('App\Tests');

        foreach ($testClasses as $testClass) {
            $suite->addTestSuite($testClass);
        }
        if ($executable && !\function_exists('App\Playground\request')) {
            throw new LogicException(sprintf('The guide "%s" is set to be executable, but no function \'request\' is defined in the \'App\Playground\' namespace', $guide));
        }
        $suite->addTestSuite(PlaygroundTestCase::class);

        PhpUnitCommand::setSuite($suite);
        $_ENV['KERNEL_CLASS'] = Kernel::class;
        $_ENV['GUIDE_NAME'] = $this->getGuideName($guide);

        return PhpUnitCommand::main(false);
    }

    /**
     * @return array|string[]
     */
    private function getDeclaredClassesForNamespace(string $namespace): array
    {
        return array_filter(get_declared_classes(), static function (string $class) use ($namespace): bool {
            return str_starts_with($class, $namespace);
        });
    }

    private function getGuideName(string $guide): string
    {
        $expl = explode('/', $guide);

        return str_replace('.php', '', end($expl));
    }

    private function getFrontMatter(string $guide): array
    {
        $handle = fopen($guide, 'r');

        $frontMatterOpen = false;
        $closing = false;
        $matter = [];
        while ($line = fgets($handle)) {
            if (preg_match(GuideCommand::REGEX, $line)) {
                $text = preg_replace(GuideCommand::REGEX, '', $line);
                if ('---' === trim($text)) {
                    if ($frontMatterOpen) {
                        $closing = true;
                    }
                    $frontMatterOpen = !$frontMatterOpen;
                    if ($closing) {
                        break;
                    }
                    continue;
                }

                if ($frontMatterOpen) {
                    $text = str_replace("\n", '', $text);
                    $expl = explode(':', $text, 2);
                    $matter[$expl[0]] = $expl[1];
                }
            }
        }
        fclose($handle);

        return $matter;
    }
}
