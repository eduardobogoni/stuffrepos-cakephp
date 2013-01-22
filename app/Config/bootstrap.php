<?php

require_once(dirname(__FILE__).'/../../stuffrepos-cakephp/cakephp/app/Config/bootstrap.php');
StuffreposBootstrap::run();
CakePlugin::load('Widgets', array('bootstrap' => true));
CakePlugin::load('ExtendedScaffold', array('bootstrap' => true));