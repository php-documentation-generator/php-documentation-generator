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

final class IndexCommandTest extends KernelTestCase
{
    private ApplicationTester $tester;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function setUp(): void
    {
        putenv('PDG_CONFIG_FILE=tests/Command/index.config.yaml');

        $kernel = self::bootKernel();
        /** @var Application $application */
        $application = $kernel->getContainer()->get(Application::class);
        $application->setAutoExit(false);
        $this->tester = new ApplicationTester($application);
    }

    public function testItOutputsIndexInAFile(): void
    {
        $output = 'tests/Command/pages/index.md';
        $this->tester->run([
            'command' => 'index',
            '--output' => $output,
        ]);

        $this->tester->assertCommandIsSuccessful(sprintf('Command failed: %s', $this->tester->getDisplay(true)));
        $this->assertFileExists($output);
        $this->assertFileEquals(
            'tests/Command/expected/index.md',
            $output
        );
    }

    public function testItOutputsIndexInCommandOutput(): void
    {
        $this->tester->run([
            'command' => 'index',
        ]);

        $this->tester->assertCommandIsSuccessful(sprintf('Command failed: %s', $this->tester->getDisplay(true)));
        $display = preg_replace("/ {2,}\n/", "\n", preg_replace("/\n /", "\n", $this->tester->getDisplay(true)));
        $this->assertStringContainsString('## Guides', $display);
        $this->assertStringContainsString('## References', $display);
    }
}
