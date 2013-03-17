<?php

App::uses('PluginManager', 'PluginManager.Lib');

class Plugin {

    /**
     *
     * @var string
     */
    private $name;

    /**
     *
     * @var string[]
     */
    private $dependencies = array();

    public function __construct($name, $dependencies) {
        $this->name = $name;
        $this->dependencies = $dependencies;
    }

    public function getName() {
        return $this->name;
    }

    public function dependencies() {
        return $this->dependencies;        
    }

    public function load() {
        foreach ($this->dependencies as $pluginName) {
            self::_loadPlugin($pluginName);
        }
    }

    private static function _loadPlugin($name) {
        CakePlugin::load($name, array('bootstrap' => self::_pluginHasBootstrapFile($name)));
    }

    private static function _pluginHasBootstrapFile($name) {
        return is_file(self::_pluginPath($name) . DS . 'Config' . DS . 'bootstrap.php');
    }

    private static function _pluginPath($name) {
        if (CakePlugin::loaded($name)) {
            return CakePlugin::path($name);
        }

        foreach (App::path('plugins') as $path) {
            if (is_dir($path . $name)) {
                return $path . $name . DS;
            }
        }

        throw new Exception("Was not possible to determinate the location of plugin \"$name\"");
    }

}