<?php

class AppId {

    public static function fetchAppId() {
        define('APP_ID', self::_getFetchAppId());
    }

    private static function _getFetchAppId() {
        if (file_exists(self::_getAppIdFile())) {
            $appId = file_get_contents(self::getAppIdFile());
        } else {
            $appId = self::_getAppPath();
        }
        return self::_quoteAppId($appId);
    }

    private static function _getAppIdFile() {
        return self::_getAppPath() . DIRECTORY_SEPARATOR . 'appid';
    }

    private static function _getAppPath() {
        if (defined('APP')) {
            return APP;
        } else if (defined('APP_DIR')) {
            return APP_DIR;
        } else if (($path = self::_getAppPathFromArgv())) {
            return $path;
        } else if (($path = self::_getAppPathFromIndexPath())) {
            return $path;
        } else {
            throw new Exception("APP path not found");
        }
    }

    private static function _getAppPathFromArgv() {
        global $argv;
        for ($k = 0; $k < count($argv); ++$k) {
            if ($argv[$k] == '-working' && !empty($argv[$k + 1])) {
                return $argv[$k + 1];
            }
        }
        return false;
    }

    private static function _getAppPathFromIndexPath() {
        $script = realpath(filter_input(INPUT_SERVER, 'SCRIPT_FILENAME'));
        return basename(dirname($script)) == 'webroot' ?
                dirname(dirname($script)) :
                dirname($script);
    }

    private static function _quoteAppId($appId) {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $appId);
    }

}
