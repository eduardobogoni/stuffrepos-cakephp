<?php

class AllOperationsPluginTest extends PHPUnit_Framework_TestSuite {

    /**
     * suite method, defines tests for this suite.
     *
     * @return void
     */
    public static function suite() {
        $suite = new CakeTestSuite("All Operations plugin's tests");
        $suite->addTestDirectoryRecursive(dirname(__FILE__));        
        return $suite;
    }

}