<?php

class ClassSearcher {

    /**
     * 
     * @param string $path
     * @return string[]
     */
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

    /**
     * 
     * @param string $path
     * @return object[]
     */
    public static function findInstances($path) {
        $instances = array();
        foreach(self::findClasses($path) as $className => $path) {
            $instances[] = self::_createInstance($className, $path);
        }
        return $instances;
    }
    
    /**
     * 
     * @param string $path
     * @param string $className
     * @return object
     * @throws Exception
     */
    public static function findInstance($path, $className) {
        $classes = self::findClasses($path);
        if (empty($classes[$className])) {
            throw new Exception("Class \"$className\" not found");
        } else {
            return self::_createInstance($className, $classes[$className]);
        }
    }

    /**
     * 
     * @param string $path
     * @param string $className
     * @return object
     * @deprecated Uses ClassSearcher::findInstance().
     */
    public static function findInstanceAndInstantiate($path, $className) {
        return self::findInstance($path, $className);
    }
    
    /**
     * 
     * @param string $className
     * @param string $path
     * @return object
     */
    private static function _createInstance($className, $path) {
        App::uses($className, $path);
        return new $className();
    }

    /**
     * 
     * @param string $pluginName
     * @param string  $pluginRoot
     * @param string $path
     * @return array
     */
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
