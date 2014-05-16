<?php

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
define('CAKE_CORE_INCLUDE_PATH', dirname(__FILE__) . DS . 'cakephp' . DS . 'lib');

ini_set(
        'include_path'
        , ini_get('include_path') . PATH_SEPARATOR . CAKE_CORE_INCLUDE_PATH
);
if (!defined('CORE_PATH')) {
    define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
}

require_once(dirname(__FILE__) . DS . 'AppId.php');
AppId::fetchAppId();
require_once(dirname(__FILE__) . DS . 'TempDirectory.php');
define('TMP', TempDirectory::currentDirectory());
TempDirectory::checkCurrentDirectory();
date_default_timezone_set('America/Sao_Paulo');
