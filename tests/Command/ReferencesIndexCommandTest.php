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

final class ReferencesIndexCommandTest extends KernelTestCase
{
    private ApplicationTester $tester;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function setUp(): void
    {
        putenv(sprintf('PDG_CONFIG_FILE=%s/reference.config.yaml', __DIR__));

        $kernel = self::bootKernel();
        /** @var Application $application */
        $application = $kernel->getContainer()->get(Application::class);
        $application->setAutoExit(false);
        $this->tester = new ApplicationTester($application);
    }

    public function testItOutputsIndexInCommandOutput(): void
    {
        $this->tester->run([
            'command' => 'references:index',
        ]);

        $this->tester->assertCommandIsSuccessful(sprintf('Command failed: %s', $this->tester->getDisplay(true)));
        $display = preg_replace("/ {2,}\n/", "\n", preg_replace("/\n /", "\n", $this->tester->getDisplay(true)));
        $this->assertStringContainsString(<<<EOT
<article className="api-list-container">
## PhpDocumentGenerator\Tests\Command\App\Controller
EOT
            , $display);
    }

    public function testItOutputsIndexInAFile(): void
    {
        $output = 'tests/Command/pages/references';
        $this->tester->run([
            'command' => 'references:index',
            '--output' => $output,
        ]);

        $this->tester->assertCommandIsSuccessful(sprintf('Command failed: %s', $this->tester->getDisplay(true)));
        $this->assertFileExists(sprintf('%s/index.mdx', $output));
        $this->assertFileEquals(
            sprintf('%s/expected/references/index.mdx', __DIR__),
            sprintf('%s/index.mdx', $output)
        );
    }
}
