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

namespace PhpDocumentGenerator\Tests\Command;

use PhpDocumentGenerator\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\ApplicationTester;

final class ReferencesCommandTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testItReturnsAWarningIfNoFilesWereFound(): void
    {
        putenv('PDG_CONFIG_FILE=tests/Command/empty.config.yaml');

        $kernel = self::bootKernel();
        /** @var Application $application */
        $application = $kernel->getContainer()->get(Application::class);
        $application->setAutoExit(false);
        $tester = new ApplicationTester($application);
        @mkdir('/tmp/pdg/empty', 0755, true);

        $tester->run([
            'command' => 'references',
        ]);

        $this->assertEquals(Command::INVALID, $tester->getStatusCode());
        $this->assertStringContainsString('No files were found in "/tmp/pdg/empty".', $tester->getDisplay(true));
    }

    public function testItOutputsEachReferenceInAFile(): void
    {
        putenv('PDG_CONFIG_FILE=tests/Command/pdg.config.yaml');

        $kernel = self::bootKernel();
        /** @var Application $application */
        $application = $kernel->getContainer()->get(Application::class);
        $application->setAutoExit(false);
        $tester = new ApplicationTester($application);

        $output = '/tmp/pdg/references';
        $tester->run([
            'command' => 'references',
            'output' => $output,
        ]);

        $tester->assertCommandIsSuccessful(sprintf('Command failed: %s', $tester->getDisplay(true)));
        $this->assertFileEquals(
            'tests/Command/expected/references/Controller/IndexController.md',
            sprintf('%s/Controller/IndexController.md', $output)
        );
    }

    public function testItOutputsEachReferenceInAFileUsingConfiguration(): void
    {
        putenv('PDG_CONFIG_FILE=tests/Command/pdg.config.yaml');

        $kernel = self::bootKernel();
        /** @var Application $application */
        $application = $kernel->getContainer()->get(Application::class);
        $application->setAutoExit(false);
        $tester = new ApplicationTester($application);

        $tester->run([
            'command' => 'references',
        ]);

        $tester->assertCommandIsSuccessful(sprintf('Command failed: %s', $tester->getDisplay(true)));
        $this->assertFileEquals(
            'tests/Command/expected/references/Controller/IndexController.md',
            'tests/Command/pages/references/Controller/IndexController.md'
        );
    }

    public function testSkipPaths(): void
    {
        putenv('PDG_CONFIG_FILE=tests/Command/pdg.config.yaml');

        $kernel = self::bootKernel();
        /** @var Application $application */
        $application = $kernel->getContainer()->get(Application::class);
        $application->setAutoExit(false);
        $tester = new ApplicationTester($application);

        $tester->run([
            'command' => 'references',
            '--exclude-path' => 'Serializer/',
            '-vvv',
        ]);

        $tester->assertCommandIsSuccessful(sprintf('Command failed: %s', $tester->getDisplay(true)));
        $this->assertStringNotContainsString('Processing tests/Command/src/Serializer/DateTimeDenormalizer.php => tests/Command/pages/references/Serializer/DateTimeDenormalizer.md', $tester->getDisplay(true));
    }
}
