<?php

class AllConfigurationKeysPluginTest extends PHPUnit_Framework_TestSuite {

    /**
     * suite method, defines tests for this suite.
     *
     * @return void
     */
    public static function suite() {
        $suite = new CakeTestSuite('All console classes');
        $suite->addTestDirectoryRecursive(dirname(__FILE__));        
        return $suite;
    }

}