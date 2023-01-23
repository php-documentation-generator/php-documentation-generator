<?php

namespace ApiPlatform\PDGBundle\Tests\TestBundle\Command;

use PHPUnit\Framework\TestSuite;
use PHPUnit\TextUI\Command;

class PhpUnitCommand extends Command {

    static $suite;

    static function setSuite(TestSuite $suite) {
        self::$suite = $suite;
    }

    /**
     * Custom callback for test suite discovery.
     */
    protected function handleCustomTestSuite(): void
    {
        $this->arguments['test'] = self::$suite;
    }
}