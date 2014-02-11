<?php

class DatasourceDumperManager {

    /**
     * 
     * @param \DataSource $ds
     * @return DatasourceDumper
     */
    public static function getDumper(\DataSource $ds) {
        $path = explode('/', $ds->config['datasource']);
        $className = end($path) . 'Dumper';
        array_pop($path);
        $path = array_merge(array('Lib', 'DatasourceDumper'), $path);
        $location = implode('/', $path);
        $plugins = array_merge(
                array(false)
                , CakePlugin::loaded()
        );
        foreach ($plugins as $plugin) {
            App::uses($className, $plugin ? "$plugin.$location" : $location);
            if (class_exists($className)) {
                return new $className;
            }
        }
        throw new Exception("Class \"$className\" not found");
    }

    private static function _listDumps() {
        $dir = new DirectoryIterator($this->_getDumpsDirectory());
        $dumps = array();
        while ($dir->valid()) {
            if ($dir->isFile()) {
                $dumps[] = $this->_parseDumpFile($dir->getPathname());
            }
            $dir->next();
        }
        return $dumps;
    }

    private static function _findDump() {
        foreach (self::_listDumps() as $dump) {
            if ($dump['name'] == $this->dumpName) {
                return $dump;
            }
        }
        return false;
    }

    private function _parseDumpFile($filepath) {
        $connection = '[_a-zA-Z][_a-zA-Z0-9]*';
        $date = '\d{4}\-\d{2}\-\d{2}_\d{2}\-\d{2}\-\d{2}';
        $datasource = '([_a-zA-Z][_a-zA-Z0-9]*)(\-[_a-zA-Z][_a-zA-Z0-9]*)*';
        $pattern = "/($connection)_($date)_($datasource)/";
        if (preg_match($pattern, basename($filepath), $matches)) {
            return array(
                'name' => basename($filepath),
                'path' => $filepath,
                'connection' => $matches[1],
                'date' => str_replace('_', ' ', $matches[2]),
                'datasource' => str_replace('-', '/', $matches[3])
            );
        } else {
            throw new Exception("File \"$filepath\" has no dump format");
        }
    }

}
