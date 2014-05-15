<?php

class IncludePath {

    private static $autoloadInitialized = false;

    public static function initAutoload() {
        if (!self::$autoloadInitialized) {
            spl_autoload_register(array('IncludePath', 'loadClass'));
            self::$autoloadInitialized = true;
        }
    }

    public static function addPath($path) {
        set_include_path(get_include_path() . PATH_SEPARATOR . $path);
    }

    public static function loadClass($className) {
        $className = ltrim($className, '\\');
        $fileName = '';
        $namespace = '';
        if ($lastNsPos = strrpos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
        if (!@include($fileName)) {
            throw new Exception("Failed to include '$fileName'");
        }
    }

}
