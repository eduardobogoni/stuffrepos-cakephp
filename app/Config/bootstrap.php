<?php

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

require_once(dirname(dirname(dirname(__FILE__))) . DS . 'stuffrepos-cakephp' . DS . 'cakephp' . DS . 'app' . DS . 'Config' . DS . 'bootstrap.php');
require_once(dirname(dirname(dirname(__FILE__))) . DS . 'stuffrepos-cakephp' . DS . 'bootstrap.php');
PluginManager::init('app', array(
    'AccessControl',
    'ExtendedScaffold',
    'Widgets',
));