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
            'command' => 'guide',
            'filename' => 'tests/Command/src/Invalid/Invalid.php',
        ]);

        $this->assertEquals(Command::INVALID, $this->tester->getStatusCode());
    }

    /**
     * @dataProvider getGuides
     */
    public function testItOutputsAGuideInAFile(string $name): void
    {
        $output = sprintf('tests/Command/pages/guides/%s.mdx', $name);
        $filename = sprintf('tests/Command/guides/%s.php', $name);
        $this->tester->run([
            'command' => 'guide',
            'filename' => $filename,
            'output' => $output,
        ]);

        $this->tester->assertCommandIsSuccessful(sprintf('Command failed: %s', $this->tester->getDisplay(true)));
        $this->assertStringContainsString('[INFO] Creating guide', $this->tester->getDisplay(true));
        $this->assertFileExists($output);
        $this->assertFileEquals(
            sprintf('%s/expected/guides/%s.mdx', __DIR__, $name),
            $output
        );
    }

    public function getGuides(): iterable
    {
        yield ['handle-a-pagination-on-a-custom-collection'];
        yield ['use-doctrine-orm-filters'];
    }
}
