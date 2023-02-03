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

final class ReferenceCommandTest extends KernelTestCase
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

    public function testItThrowsAnErrorIfFileDoesNotExist(): void
    {
        $this->tester->run([
            'command' => 'reference',
            'filename' => 'tests/Command/src/Invalid/Invalid.php',
        ]);

        $this->assertEquals(Command::FAILURE, $this->tester->getStatusCode());
        $this->assertStringContainsString(<<<EOT
File "tests/Command/src/Invalid/Invalid.php" does not exist.
EOT
            , $this->tester->getDisplay(true));
    }

    public function testItOutputsAReferenceInCommandOutput(): void
    {
        $filename = 'tests/Command/src/Controller/IndexController.php';
        $this->tester->run([
            'command' => 'reference',
            'filename' => $filename,
        ]);

        $this->tester->assertCommandIsSuccessful(sprintf('Command failed: %s', $this->tester->getDisplay(true)));
        $this->assertStringContainsString('[INFO] Creating reference', $this->tester->getDisplay(true));
        $this->assertStringContainsString(sprintf('"%s"', $filename), $this->tester->getDisplay(true));
        $display = preg_replace("/ {2,}\n/", "\n", preg_replace("/\n /", "\n", $this->tester->getDisplay(true)));
        $this->assertStringContainsString(<<<EOT
import Head from "next/head";

<Head><title>IndexController</title></Head>

# \PhpDocumentGenerator\Tests\Command\App\Controller\IndexController
EOT
            , $display);
    }

    /**
     * @dataProvider getReferences
     */
    public function testItOutputsAReferenceInAFile(string $name): void
    {
        $output = sprintf('tests/Command/pages/references/%s.mdx', $name);
        $filename = sprintf('tests/Command/src/%s.php', $name);
        $this->tester->run([
            'command' => 'reference',
            'filename' => $filename,
            'output' => $output,
        ]);

        $this->tester->assertCommandIsSuccessful(sprintf('Command failed: %s', $this->tester->getDisplay(true)));
        $this->assertStringContainsString('[INFO] Creating reference', $this->tester->getDisplay(true));
        $this->assertStringContainsString(sprintf('"%s"', $filename), $this->tester->getDisplay(true));
        $this->assertFileExists($output);
        $this->assertFileEquals(
            sprintf('%s/expected/references/%s.mdx', __DIR__, $name),
            $output
        );
    }

    public function getReferences(): iterable
    {
        yield ['Controller/IndexController'];
        yield ['DependencyInjection/Configuration'];
        yield ['Serializer/DateTimeDenormalizer'];
    }
}
