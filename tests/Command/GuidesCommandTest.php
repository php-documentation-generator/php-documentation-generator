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

class GuidesCommandTest extends KernelTestCase
{
    private ApplicationTester $tester;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function setUp(): void
    {
        putenv('PDG_CONFIG_FILE=tests/Command/pdg.config.yaml');

        $kernel = self::bootKernel();
        /** @var Application $application */
        $application = $kernel->getContainer()->get(Application::class);
        $application->setAutoExit(false);
        $this->tester = new ApplicationTester($application);
    }

    public function testItOutputsEachReferenceInAFile(): void
    {
        $output = 'tests/Command/pages/guides';
        $this->tester->run([
            'command' => 'guides',
            'output' => $output,
            '--directory' => 'tests/Command/guides',
        ]);

        $this->tester->assertCommandIsSuccessful(sprintf('Command failed: %s', $this->tester->getDisplay(true)));
        $this->assertFileEquals(
            'tests/Command/expected/guides/use-doctrine.md',
            sprintf('%s/use-doctrine.md', $output)
        );
    }
}
