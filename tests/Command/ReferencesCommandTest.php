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

final class ReferencesCommandTest extends KernelTestCase
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

    public function testItOutputsEachReferenceInCommandOutput(): void
    {
        $this->tester->run([
            'command' => 'references',
        ]);

        $this->tester->assertCommandIsSuccessful(sprintf('Command failed: %s', $this->tester->getDisplay(true)));
        $this->assertStringContainsString('[INFO] Creating reference', $this->tester->getDisplay(true));
        $this->assertStringContainsString('"tests/Command/src/Controller/IndexController.php"', $this->tester->getDisplay(true));
        $display = preg_replace("/ {2,}\n/", "\n", preg_replace("/\n /", "\n", $this->tester->getDisplay(true)));
        // cannot test full output cause the output size differs locally or on GitHub
        $this->assertStringContainsString(<<<EOT
import Head from "next/head";

<Head><title>IndexController</title></Head>

# \PhpDocumentGenerator\Tests\Command\App\Controller\IndexController
EOT
            , $display);
        $this->assertStringContainsString(<<<EOT
<article class="api-list-container">
## PhpDocumentGenerator\Tests\Command\App\Controller
EOT
            , $display);
    }

    public function testItOutputsEachReferenceInAFile(): void
    {
        $output = 'tests/Command/pages/references';
        $this->tester->run([
            'command' => 'references',
            'output' => $output,
        ]);

        $this->tester->assertCommandIsSuccessful(sprintf('Command failed: %s', $this->tester->getDisplay(true)));
        $this->assertStringContainsString('[INFO] Creating reference "tests/Command/src/Controller/IndexController.php".', $this->tester->getDisplay(true));
        $this->assertFileExists(sprintf('%s/Controller/IndexController.mdx', $output));
        $this->assertFileEquals(
            sprintf('%s/expected/references/Controller/IndexController.mdx', __DIR__),
            sprintf('%s/Controller/IndexController.mdx', $output)
        );
    }
}
