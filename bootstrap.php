<?php

App::build(
        array(
            'Plugin' => dirname(__FILE__) . DS . 'plugins' . DS
        )
);

require_once(dirname(__FILE__) . DS . 'cakephp' . DS . 'app' . DS . 'Config' . DS . 'bootstrap.php');

CakePlugin::load('PluginManager', array('bootstrap' => true));
App::uses('PluginManager', 'PluginManager.Lib');
