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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class GuidesCommandTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    private function getApplicationTester(): ApplicationTester
    {
        $kernel = self::bootKernel();
        /** @var Application $application */
        $application = $kernel->getContainer()->get(Application::class);
        $application->setAutoExit(false);

        return new ApplicationTester($application);
    }

    private function getOutputDirectory(): string
    {
        return Path::join(sys_get_temp_dir(), '/pdg');
    }

    protected function setUp(): void
    {
        @mkdir($this->getOutputDirectory(), 0755, true);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->getOutputDirectory());
    }

    public function testItReturnsAWarningIfNoFilesWereFound(): void
    {
        $tester = $this->getApplicationTester();

        $tester->run([
            'command' => 'guides',
            'src' => Path::join($this->getOutputDirectory(), 'empty'),
        ]);

        $this->assertEquals(Command::FAILURE, $tester->getStatusCode());
    }

    public function testItOutputsEachReferenceInAFile(): void
    {
        $tester = $this->getApplicationTester();
        $output = Path::join($this->getOutputDirectory(), 'guides');
        $tester->run([
            'command' => 'guides',
            'output' => $output,
            'directory' => 'tests/Fixtures/guides',
        ]);

        $tester->assertCommandIsSuccessful(sprintf('Command failed: %s', $tester->getDisplay(true)));
        $this->assertFileExists(Path::join($output, 'use-doctrine.md'));
    }
}
