<?php

if (!defined('ROOT')) {
    define('ROOT', dirname(dirname(dirname(__FILE__))));
}

if (!defined('APP_DIR')) {
    define('APP_DIR', basename(dirname(dirname(__FILE__))));
}

require_once(dirname(__FILE__) . '/../../stuffrepos-cakephp/include.php');
require_once(dirname(__FILE__) . '/../../stuffrepos-cakephp/cakephp/app/webroot/index.php');