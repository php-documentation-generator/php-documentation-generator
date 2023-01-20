<?php

declare(strict_types=1);

namespace PDG\Tests\TestBundle\Command;

use App\Kernel;
use Doctrine\Migrations\Version\Direction;
use PHPUnit\Framework\TestSuite;
use PHPUnit\TextUI\Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:test:guide')]
class TestGuideCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument(
            'guide',
            InputArgument::REQUIRED,
            'the path to the guide to test'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $guide = $input->getArgument('guide');
        $style->info('Testing guide: ' . $guide);

        $suite = new TestSuite();

//        require $guide;

        $testClasses = $this->getDeclaredClassesForNamespace('App\Tests');
        $migrationClasses = $this->getDeclaredClassesForNamespace('DoctrineMigrations');

        /** @var Kernel $app */
        $app = $this->getApplication()->getKernel();
        if ($migrationClasses) {
            $app->executeMigrations();
        }

        foreach ($testClasses as $testClass) {
            $suite->addTestSuite($testClass);
        }

        PhpUnitCommand::setSuite($suite);
        try {
            $result = PhpUnitCommand::main(false);
        } catch (Exception $e) {
            if ($migrationClasses) {
                $app->executeMigrations(Direction::DOWN);
            }
            $this->deleteDir($app->getCacheDir());
            throw $e;
        }
        if ($migrationClasses) {
            $app->executeMigrations(Direction::DOWN);
        }
//        $this->deleteDir($app->getCacheDir());

        return $result;
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

    private function deleteDir(string $directory): bool
    {
        if (!\file_exists($directory)) {
            return true;
        }

        if (!\is_dir($directory)) {
            return \unlink($directory);
        }

        foreach (\scandir($directory) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            if (!$this->deleteDir($directory.\DIRECTORY_SEPARATOR.$item)) {
                return false;
            }
        }

        return \rmdir($directory);
    }
}
