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

namespace PhpDocumentGenerator\Tests\Services;

use PhpDocumentGenerator\Services\ConfigurationHandler;
use PHPUnit\Framework\TestCase;

final class ConfigurationHandlerTest extends TestCase
{
    /**
     * @dataProvider getConfigs
     */
    public function testItGetsAConfigurationValueFromTheConfigFile(string $key, mixed $expected): void
    {
        putenv('PDG_CONFIG_FILE=tests/Services/pdg.config.yaml');

        $this->assertEquals($expected, (new ConfigurationHandler())->get($key));
    }

    public function getConfigs(): iterable
    {
        yield ['autoload', 'vendor/autoload.php'];
        yield ['references.src', 'tests/Command/src'];
        yield ['references.namespace', 'PhpDocumentGenerator\Tests\Command\App'];
        yield ['references.patterns.directories', ['Controller', 'Validator']];
        yield ['references.patterns.names', ['*.php']];
        yield ['references.patterns.exclude', ['*Interface.php']];
        yield ['references.patterns.class_tags_to_ignore', ['@internal', '@experimental']];
        yield ['references.output', 'tests/Command/pages/references'];
        yield ['references.base_url', '/pages/references'];
        yield ['guides.output', 'tests/Command/pages/guides'];
        yield ['guides.base_url', '/pages/guides'];
    }
}
