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

namespace ApiPlatform\PDGBundle\Tests\Services;

use ApiPlatform\PDGBundle\Services\ConfigurationHandler;
use PHPUnit\Framework\TestCase;

final class ConfigurationHandlerTest extends TestCase
{
    /**
     * @dataProvider getConfigs
     */
    public function testItGetsAConfigurationValueFromTheConfigFile(string $key, mixed $expected): void
    {
        putenv(sprintf('PDG_CONFIG_FILE=%s/pdg.config.yaml', __DIR__));

        $this->assertEquals($expected, (new ConfigurationHandler())->get($key));
    }

    public function getConfigs(): iterable
    {
        yield ['autoload', 'vendor/autoload.php'];
        yield ['reference.src', 'src'];
        yield ['reference.namespace', 'App'];
        yield ['reference.patterns.directories', ['Controller', 'Validator']];
        yield ['reference.patterns.names', ['*.php']];
        yield ['reference.patterns.exclude', ['*Interface.php']];
        yield ['reference.patterns.class_tags_to_ignore', ['@internal', '@experimental']];
        yield ['target.directories.guide_path', 'pages/guides'];
        yield ['target.directories.reference_path', 'pages/references'];
        yield ['target.base_path', 'pages'];
    }
}
