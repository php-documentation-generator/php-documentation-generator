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

namespace PhpDocumentGenerator\Playground;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use LogicException;

use function App\Playground\request; // @phpstan-ignore-line

class PlaygroundTestCase extends ApiTestCase
{
    use TestGuideTrait;

    public function testGuideRequest(): void
    {
        if (!\function_exists('App\Playground\request')) {
            $this->markTestSkipped('No function request defined');
        }

        $kernel = static::createKernel();
        if (!\function_exists('App\Playground\request')) {
            throw new LogicException('Unable to perform a request. Did you forget to setup the request?');
        }

        $request = request();
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        $this->assertLessThan(500, $response->getStatusCode());
    }
}
