<?php

if (!defined('ROOT')) {
    define('ROOT', dirname(dirname(dirname(__FILE__))));
}

$ds = DIRECTORY_SEPARATOR;
require_once(dirname(dirname(dirname(__FILE__))) . $ds . 'stuffrepos-cakephp' . $ds . 'include.php');
require_once(dirname(dirname(dirname(__FILE__))) . $ds . 'stuffrepos-cakephp' . $ds . 'cakephp' . $ds . 'app' . $ds . 'webroot' . $ds . 'index.php');
