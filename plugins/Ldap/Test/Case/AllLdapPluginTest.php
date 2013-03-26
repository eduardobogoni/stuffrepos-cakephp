<?php

class AllLdapPluginTest extends PHPUnit_Framework_TestSuite {

    /**
     * suite method, defines tests for this suite.
     *
     * @return void
     */
    public static function suite() {
        $suite = new CakeTestSuite('All LDAP plugin tests');
        $suite->addTestDirectoryRecursive(dirname(__FILE__));        
        return $suite;
    }

}