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

use PHPUnit\Event\Facade as EventFacade;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Timer\Timer;
use PHPUnit\TestRunner\TestResult\Facade as TestResultFacade;
use PHPUnit\TextUI\Output\Facade as OutputFacade;
use PHPUnit\Runner\ResultCache\NullResultCache;
use PHPUnit\Framework\TestSuite;
use PHPUnit\TextUI\Configuration\Builder as ConfigurationBuilder;
use PHPUnit\TextUI\TestRunner;
use PhpDocumentGenerator\Configuration;
use PhpDocumentGenerator\Playground\PlaygroundTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class TestGuideCommand extends Command
{
    public function __construct(
        private readonly Configuration $configuration,
    ) {
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
        $style->info('Testing guide: ' . $guide);

        $_ENV['GUIDE_NAME'] = $this->getGuideName($guide);
        $configuration = (new ConfigurationBuilder)->build([]);
        $suite = TestSuite::fromClassReflector(new \ReflectionClass(PlaygroundTestCase::class));
        $testClasses = $this->getDeclaredClassesForNamespace('App\Tests');
        foreach ($testClasses as $testClass) {
            if (is_a($testClass, TestCase::class, true)) {
                $suite->addTestSuite(new \ReflectionClass($testClass));
            }
        }

        $printer = OutputFacade::init(
            $configuration,
            false,
            false
        );

        TestResultFacade::init();
        EventFacade::instance()->seal();

        $timer = new Timer;
        $timer->start();
        $testRunner = new TestRunner();
        $testRunner->run($configuration, new NullResultCache(), $suite);
        $duration = $timer->stop();

        $result = TestResultFacade::result();
        OutputFacade::printResult($result, null, $duration);

        return 0;
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
}
