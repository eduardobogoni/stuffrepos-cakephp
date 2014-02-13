<?php

class TempDirectory {

    /**
     * Retorna o caminho para o diretório de temporários
     * corrente.
     * @return string
     */
    public static function currentDirectory() {
        if (defined('TMP')) {
            return TMP;
        } else {
            return self::_rootDirectory() . self::_appId() . DS;
        }
    }

    private static function _appId() {
        return defined('APP_ID') ? APP_ID : 'undefined-app-id';
    }

    /**
     * Retorna o caminho para o diretório de diretórios temporários
     * de aplicações.     
     * @return string
     */
    private static function _rootDirectory() {
        return self::_homeDirectory() . DS . '.cakephp-tmp' . DS;
    }

    public static function checkCurrentDirectory() {
        $tmpSkeletonRootPath = realpath(dirname(__FILE__) . DS . 'cakephp' . DS . 'app' . DS . 'tmp');
        self::_checkTemporaryDirectory(
                $tmpSkeletonRootPath
                , new DirectoryIterator($tmpSkeletonRootPath)
        );
    }

    /**
     * 
     * @return string
     */
    private static function _homeDirectory() {
        if (function_exists('posix_getuid')) {
            return self::_posixHomeDirectory(posix_getuid());
        } else {
            return sys_get_temp_dir();
        }
    }

    /**
     * Retorna o caminho para o diretório HOME de um usuário Posix.
     * @param int|string $uid
     * @return string
     */
    private static function _posixHomeDirectory($uid) {
        if (is_int($uid)) {
            $userConfig = posix_getpwuid($uid);
        } else {
            $userConfig = posix_getpwnam($uid);
        }
        return (empty($userConfig['dir']) ? sys_get_temp_dir() : $userConfig['dir']);
    }

    // Configuração de diretório de temporários por usuário.
    public static function cakephpAppTemporaryDirectory($uid) {
        return cakephpRootTemporaryDirectory($uid) . (defined('APP_ID') ? APP_ID : 'undefined-app-id') . DS;
    }

    private static function _removeStringPrefix($prefix, $string) {
        if (substr($string, 0, strlen($prefix)) == $prefix) {
            return substr($string, strlen($prefix), strlen($string));
        } else {
            return $string;
        }
    }

    private static function _checkTemporaryDirectory($tmpSkeletonRootPath, DirectoryIterator $tmpSkeletonSubDir) {
        while ($tmpSkeletonSubDir->valid()) {
            if ($tmpSkeletonSubDir->isDir() && !$tmpSkeletonSubDir->isDot()) {
                $relativePath = self::_removeStringPrefix($tmpSkeletonRootPath,
                                $tmpSkeletonSubDir->getRealPath());
                $fullPath = TMP . $relativePath;

                if (!file_exists($fullPath)) {
                    mkdir($fullPath, 0755, true);
                }

                self::_checkTemporaryDirectory($tmpSkeletonRootPath,
                        new DirectoryIterator($tmpSkeletonSubDir->getRealPath()));
            }
            $tmpSkeletonSubDir->next();
        }
    }

}
