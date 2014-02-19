<?php

class ClassSearcher {

    public static function findClasses($path) {
        $classes = array();
        foreach (CakePlugin::loaded() as $plugin) {
            $classes = array_merge(
                    $classes,
                    self::_findClassesOnPlugin($plugin,
                            CakePlugin::path($plugin), $path)
            );
        }
        return array_merge(
                $classes, self::_findClassesOnPlugin(false, APP, $path)
        );
    }

    public static function findInstanceAndInstantiate($path, $className) {
        $classes = self::findClasses($path);
        if (empty($classes[$className])) {
            throw new Exception("Classe \"$className\" not found");
        } else {

            App::uses($className, $classes[$className]);
            return new $className();
        }
    }

    public static function _findClassesOnPlugin($pluginName, $pluginRoot, $path) {
        $fileSystemPath = $pluginRoot . implode(DS, explode('/', $path));
        if (!is_dir($fileSystemPath)) {
            return array();
        }
        $dir = new DirectoryIterator($fileSystemPath);
        $classes = array();
        while ($dir->valid()) {
            if ($dir->isFile() && $dir->getExtension() == 'php') {
                $classes[$dir->getBasename('.' . $dir->getExtension())] = ($pluginName ? $pluginName . '.' : '') . $path;
            }
            $dir->next();
        }
        return $classes;
    }

}
