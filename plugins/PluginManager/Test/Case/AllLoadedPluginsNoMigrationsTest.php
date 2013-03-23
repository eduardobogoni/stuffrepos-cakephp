<?php

class AllLoadedPluginsNoMigrationsTest extends PHPUnit_Framework_TestSuite {

    public static function suite() {
        $suite = new CakeTestSuite('All Plugins');
        foreach (CakePlugin::loaded() as $plugin) {
            if ($plugin != 'Migrations') {
                $suite->addTestDirectoryRecursive(CakePlugin::path($plugin) . DS . 'Test' . DS . 'Case');
            }
        }
        return $suite;
    }

}