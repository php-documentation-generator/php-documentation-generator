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

class GuidesCommandTest extends KernelTestCase
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
        @mkdir('/tmp/pdg/empty', 0777, true);

        $tester->run([
            'command' => 'guides',
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

        $output = '/tmp/pdg/guides';
        $tester->run([
            'command' => 'guides',
            'output' => $output,
            '--directory' => 'tests/Command/guides',
        ]);

        $tester->assertCommandIsSuccessful(sprintf('Command failed: %s', $tester->getDisplay(true)));
        $this->assertFileEquals(
            'tests/Command/expected/guides/use-doctrine.md',
            sprintf('%s/use-doctrine.md', $output)
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
            'command' => 'guides',
        ]);

        $tester->assertCommandIsSuccessful(sprintf('Command failed: %s', $tester->getDisplay(true)));
        $this->assertFileEquals(
            'tests/Command/expected/guides/use-doctrine.md',
            'tests/Command/pages/guides/use-doctrine.md'
        );
    }
}
