<?php

App::uses('Plugin', 'PluginManager.Lib');

class PluginManager {

    private static $plugins = array();

    public static function init($pluginName, $dependencies = array()) {
        self::$plugins[$pluginName] = new Plugin($pluginName, $dependencies);
        self::$plugins[$pluginName]->load();
    }

    public static function plugin($name) {
        if (empty(self::$plugins[$name])) {
            self::init($name, array());
        }

        return self::$plugins[$name];
    }

    public static function inTree($pluginName = 'app') {
        foreach (self::plugin($pluginName)->dependencies() as $dependency) {
            foreach (self::inTree($dependency) as $subDependency) {
                $plugins[$subDependency] = true;
            }
        }
        $plugins[$pluginName] = true;
        return array_keys($plugins);
    }

    public static function notInTree($pluginName = 'app') {
        $plugins = array();
        foreach (CakePlugin::loaded() as $plugin) {
            $plugins[$plugin] = true;
        }                
        
        foreach (self::inTree($pluginName) as $plugin) {
            unset($plugins[$plugin]);            
        }
        return array_keys($plugins);
    }

}
