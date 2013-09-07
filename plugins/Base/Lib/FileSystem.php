<?php

class FileSystem {

    public static function createTemporaryDirectory($prefix = '') {

        $dir = tempnam(sys_get_temp_dir(), $prefix);
        if (file_exists($dir)) {
            unlink($dir);
        }
        mkdir($dir);
        return $dir;
    }

    public static function listFiles($directory, $recursive = false) {
        return self::_listFiles($directory, '', $recursive);
    }

    private static function _listFiles($directory, $prefix, $recursive) {
        $tree = array();
        $it = new DirectoryIterator($directory);

        while ($it->valid()) {
            if (!$it->isDot()) {
                $tree[] = $prefix . $it->getFilename();
                if ($it->isDir() && $recursive) {
                    foreach (self::_listFiles($it->getPathname(), $prefix . $it->getFilename() . '/') as $file) {
                        $tree[] = $file;
                    }
                }
            }
            $it->next();
        }

        return $tree;
    }

    /**
     * // ------------ lixlpixel recursive PHP functions -------------
      // recursive_remove_directory( directory to delete, empty )
      // expects path to directory and optional TRUE / FALSE to empty
      // of course PHP has to have the rights to delete the directory
      // you specify and all files and folders inside the directory
      // ------------------------------------------------------------
      // to use this function to totally remove a directory, write:
      // recursive_remove_directory('path/to/directory/to/delete');
      // to use this function to empty a directory, write:
      // recursive_remove_directory('path/to/full_directory',TRUE);
     * http://lixlpixel.org/recursive_function/php/recursive_directory_delete/
     */
    public static function recursiveRemoveDirectory($directory, $empty = FALSE) {
        // if the path has a slash at the end we remove it here
        if (substr($directory, -1) == '/') {
            $directory = substr($directory, 0, -1);
        }

        // if the path is not valid or is not a directory ...
        if (!file_exists($directory) || !is_dir($directory)) {
            // ... we return false and exit the function
            return FALSE;

            // ... if the path is not readable
        } elseif (!is_readable($directory)) {
            // ... we return false and exit the function
            return FALSE;

            // ... else if the path is readable
        } else {

            // we open the directory
            $handle = opendir($directory);

            // and scan through the items inside
            while (FALSE !== ($item = readdir($handle))) {
                // if the filepointer is not the current directory
                // or the parent directory
                if ($item != '.' && $item != '..') {
                    // we build the new path to delete
                    $path = $directory . '/' . $item;

                    // if the new path is a directory
                    if (is_dir($path)) {
                        // we call this function with the new path
                        self::recursiveRemoveDirectory($path);

                        // if the new path is a file
                    } else {
                        // we remove the file
                        unlink($path);
                    }
                }
            }
            // close the directory
            closedir($handle);

            // if the option to empty is not set to true
            if ($empty == FALSE) {
                // try to delete the now empty directory
                if (!rmdir($directory)) {
                    // return false if not possible
                    return FALSE;
                }
            }
            // return success
            return TRUE;
        }
    }

    public static function recursiveRemoveDirectoryInners($directory) {
        $it = new DirectoryIterator($directory);

        while ($it->valid()) {
            if (!$it->isDot()) {
                if ($it->isFile() && !unlink($it->getPathname())) {
                    return false;
                } else if (!self::recursiveRemoveDirectory($it->getPathname())) {
                    return false;
                }
            }
            $it->next();
        }
        
        return true;
    }

}