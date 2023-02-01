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
use ApiPlatform\PDGBundle\Tests\TestBundle\Command\PhpUnitCommand;
use ApiPlatform\PDGBundle\Tests\TestBundle\Guide\PlaygroundTestCase;
use App\Kernel;
use PHPUnit\Framework\TestSuite;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class TestGuideCommand extends Command
{
    public function __construct(private readonly ConfigurationHandler $configurationHandler)
    {
        parent::__construct(name: 'test:guide');
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

        $suite = new TestSuite();

        require $guide;

        $testClasses = $this->getDeclaredClassesForNamespace('App\Tests');

        foreach ($testClasses as $testClass) {
            $suite->addTestSuite($testClass);
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
}
