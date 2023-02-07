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

use PhpDocumentGenerator\Parser\ClassParser;
use PhpDocumentGenerator\Services\ConfigurationHandler;
use PhpDocumentGenerator\Tests\Command\App\Serializer\DateTimeDenormalizer;
use PhpDocumentGenerator\Tests\Command\App\Services\ExperimentalClass;
use PhpDocumentGenerator\Tests\Command\App\Services\IgnoredInterface;
use PhpDocumentGenerator\Tests\Command\App\Services\InternalClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

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

    /**
     * @dataProvider getClasses
     */
    public function testItExcludesInvalidClasses(string $className, bool $expected): void
    {
        putenv('PDG_CONFIG_FILE=tests/Services/pdg.config.yaml');

        $this->assertEquals($expected, (new ConfigurationHandler())->isExcluded(new ClassParser(new ReflectionClass($className))));
    }

    public function getClasses(): iterable
    {
        yield [IgnoredInterface::class, true];
        yield [InternalClass::class, true];
        yield [ExperimentalClass::class, true];
        yield [DateTimeDenormalizer::class, false];
    }
}
