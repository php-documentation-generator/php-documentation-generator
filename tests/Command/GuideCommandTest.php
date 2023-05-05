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

namespace PhpDocumentGenerator\Tests\Command;

use PhpDocumentGenerator\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\ApplicationTester;

final class GuideCommandTest extends KernelTestCase
{
    private ApplicationTester $tester;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var Application $application */
        $application = $kernel->getContainer()->get(Application::class);
        $application->setAutoExit(false);
        $this->tester = new ApplicationTester($application);
    }

    public function testItThrowsAnErrorIfFileDoesNotExist(): void
    {
        $this->tester->run([
            'command' => 'guide',
            'filename' => 'tests/Command/guides/invalid.php',
        ]);

        $this->assertEquals(Command::INVALID, $this->tester->getStatusCode());
        $this->assertStringContainsString(<<<EOT
File "tests/Command/guides/invalid.php" does not exist.
EOT
            , $this->tester->getDisplay(true));
    }

    public function testItOutputsAGuideInAFile(): void
    {
        $output = 'tests/Fixtures/output/guides/use-doctrine.md';
        $filename = 'tests/Fixtures/guides/use-doctrine.php';
        $this->tester->run([
            'command' => 'guide',
            'filename' => $filename,
            '--output' => $output,
        ]);

        $this->tester->assertCommandIsSuccessful(sprintf('Command failed: %s', $this->tester->getDisplay(true)));
        $this->assertStringContainsString('[OK] Guide "tests/Fixtures/guides/use-doctrine.php" successfully created.', $this->tester->getDisplay(true));
        $this->assertStringContainsString(sprintf('"%s"', $filename), $this->tester->getDisplay(true));
        $this->assertFileExists($output);
        $this->assertFileEquals(
            'tests/Fixtures/expected/guides/use-doctrine.md',
            $output
        );
    }

    public function testItOutputsAGuideInCommandOutput(): void
    {
        $filename = 'tests/Fixtures/guides/use-doctrine.php';
        $this->tester->run([
            'command' => 'guide',
            'filename' => $filename,
        ]);

        $this->tester->assertCommandIsSuccessful(sprintf('Command failed: %s', $this->tester->getDisplay(true)));
        $this->assertStringContainsString('[OK] Guide "tests/Fixtures/guides/use-doctrine.php" successfully created.', $this->tester->getDisplay(true));
        $this->assertStringContainsString(sprintf('"%s"', $filename), $this->tester->getDisplay(true));
        $this->assertStringContainsString(<<<EOT
Should be a real guide
EOT
            , $this->tester->getDisplay(true));
    }
}
