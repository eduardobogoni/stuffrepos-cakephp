<?php

class AllLoadedPluginsNoMigrationsTest extends PHPUnit_Framework_TestSuite {
    
    private static $noStable = array(
        'Migrations'
        , 'Tools'
    );

    public static function suite() {
        $suite = new CakeTestSuite('All Plugins');
        foreach (CakePlugin::loaded() as $plugin) {
            if (!in_array($plugin, self::$noStable)) {
                $suite->addTestDirectoryRecursive(CakePlugin::path($plugin) . DS . 'Test' . DS . 'Case');
            }
        }
        return $suite;
    }

}