<?php

namespace PDG\Tests\TestBundle\Command;

use PHPUnit\Framework\TestSuite;

class PhpUnitCommand extends \PHPUnit\TextUI\Command {
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