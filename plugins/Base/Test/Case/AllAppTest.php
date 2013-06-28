<?php

class AllAppTest extends PHPUnit_Framework_TestSuite {

    /**
     * suite method, defines tests for this suite.
     *
     * @return void
     */
    public static function suite() {
        $suite = new CakeTestSuite("All Base plugin's tests");
        $suite->addTestDirectoryRecursive(TESTS . DS . 'Case');
        return $suite;
    }

}