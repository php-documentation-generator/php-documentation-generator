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
        putenv('PDG_CONFIG_FILE=tests/Command/reference.config.yaml');

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

        $this->assertEquals(Command::INVALID, $this->tester->getStatusCode());
        $this->assertStringContainsString('File "tests/Command/src/Invalid/Invalid.php" does not exist.', $this->tester->getDisplay(true));
    }

    /**
     * @dataProvider getReferences
     */
    public function testItOutputsAReferenceInAFile(string $name): void
    {
        $output = sprintf('tests/Command/pages/references/%s.md', $name);
        $this->tester->run([
            'command' => 'reference',
            'filename' => sprintf('tests/Command/src/%s.php', $name),
            '--output' => $output,
        ]);

        $this->tester->assertCommandIsSuccessful(sprintf('Command failed: %s', $this->tester->getDisplay(true)));
        $this->assertFileExists($output);
        $this->assertFileEquals(
            sprintf('tests/Command/expected/references/%s.md', $name),
            $output
        );
    }

    public function getReferences(): iterable
    {
        yield ['Controller/IndexController'];
        yield ['DependencyInjection/Configuration'];
        yield ['Serializer/DateTimeDenormalizer'];
    }

    public function testItOutputsAReferenceInCommandOutput(): void
    {
        $this->tester->run([
            'command' => 'reference',
            'filename' => 'tests/Command/src/Controller/IndexController.php',
        ]);

        $this->tester->assertCommandIsSuccessful(sprintf('Command failed: %s', $this->tester->getDisplay(true)));
        $display = preg_replace("/ {2,}\n/", "\n", preg_replace("/\n /", "\n", $this->tester->getDisplay(true)));
        $this->assertStringContainsString('# \PhpDocumentGenerator\Tests\Command\App\Controller\IndexController', $display);
    }
}
