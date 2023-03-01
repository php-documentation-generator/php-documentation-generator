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
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final class ReferencesIndexCommandTest extends KernelTestCase
{
    private ApplicationTester $tester;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    private function getOutputDirectory(): string
    {
        return Path::join(sys_get_temp_dir(), '/pdg');
    }

    protected function setUp(): void
    {
        @mkdir($this->getOutputDirectory(), 0755, true);
        $kernel = self::bootKernel();
        /** @var Application $application */
        $application = $kernel->getContainer()->get(Application::class);
        $application->setAutoExit(false);
        $this->tester = new ApplicationTester($application);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->getOutputDirectory());
    }

    public function testItOutputsIndex(): void
    {
        $output = 'tests/Fixtures/output/references/index.md';
        $this->tester->run([
            'command' => 'references:index',
            'src' => 'tests/Fixtures/src',
            '--output' => $output,
            '--namespace' => 'PhpDocumentGenerator\Tests\Fixtures',
            '--base-url' => '/reference',
        ]);

        $this->tester->assertCommandIsSuccessful(sprintf('Command failed: %s', $this->tester->getDisplay(true)));
        $this->assertFileExists($output);
        $this->assertFileEquals(
            'tests/Fixtures/expected/references/index.md',
            $output
        );
    }
}
