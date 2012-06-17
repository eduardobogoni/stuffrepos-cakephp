<?php

define('CAKE_CORE_INCLUDE_PATH', dirname(__FILE__) . '/cakephp/lib');
ini_set(
        'include_path'
        , ini_get('include_path') . PATH_SEPARATOR . CAKE_CORE_INCLUDE_PATH
);
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('CORE_PATH')) {
    define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
}

class StuffreposBootstrap {

    public static function run() {
        App::build(
                array(
                    'Plugin' => dirname(__FILE__) . '/plugins/'
                )
        );

        CakePlugin::load('Migrations');
        CakePlugin::load('StuffreposBase');
    }

}