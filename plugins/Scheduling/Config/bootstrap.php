<?php

PluginManager::init('Scheduling', array());
App::uses('IncludePath', 'Base.Lib');
IncludePath::initAutoload();
IncludePath::addPath(dirname(dirname(__FILE__)) . DS . 'Vendor' . DS . 'CronExpression' . DS . 'src');