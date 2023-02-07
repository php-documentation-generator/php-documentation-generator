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

final class GuideCommandTest extends KernelTestCase
{
    private ApplicationTester $tester;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function setUp(): void
    {
        putenv('PDG_CONFIG_FILE=tests/Command/guide.config.yaml');

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
        $output = 'tests/Command/pages/guides/use-doctrine.md';
        $filename = 'tests/Command/guides/use-doctrine.php';
        $this->tester->run([
            'command' => 'guide',
            'filename' => $filename,
            '--output' => $output,
        ]);

        $this->tester->assertCommandIsSuccessful(sprintf('Command failed: %s', $this->tester->getDisplay(true)));
        $this->assertStringContainsString('[INFO] Creating guide', $this->tester->getDisplay(true));
        $this->assertStringContainsString(sprintf('"%s"', $filename), $this->tester->getDisplay(true));
        $this->assertFileExists($output);
        $this->assertFileEquals(
            'tests/Command/expected/guides/use-doctrine.md',
            $output
        );
    }

    public function testItOutputsAGuideInCommandOutput(): void
    {
        $filename = 'tests/Command/guides/use-doctrine.php';
        $this->tester->run([
            'command' => 'guide',
            'filename' => $filename,
        ]);

        $this->tester->assertCommandIsSuccessful(sprintf('Command failed: %s', $this->tester->getDisplay(true)));
        $this->assertStringContainsString('[INFO] Creating guide', $this->tester->getDisplay(true));
        $this->assertStringContainsString(sprintf('"%s"', $filename), $this->tester->getDisplay(true));
        $display = preg_replace("/ {2,}\n/", "\n", preg_replace("/\n /", "\n", $this->tester->getDisplay(true)));
        $this->assertStringContainsString(<<<EOT
<a href="#section-1" id="section-1">§</a>

Should be a real guide

```php
// src/App/Entity.php
namespace App\Entity;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
/**
 * Book.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
#[ApiResource]
#[ORM\Entity]
class Book
EOT
            , $display);
    }
}
