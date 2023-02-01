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

namespace ApiPlatform\PDGBundle\Tests\TestBundle\Guide;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

use function App\Playground\request;

class PlaygroundTestCase extends ApiTestCase
{
    public function testGuideRequest(): void
    {
        if (!\function_exists('App\Playground\request')) {
            $this->markTestSkipped('No function request defined');

            return;
        }

        $kernel = static::createKernel();
        $request = request();
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        $this->assertLessThan(500, $response->getStatusCode());
    }
}
