<?php

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
define('CAKE_CORE_INCLUDE_PATH', dirname(__FILE__) . DS . 'cakephp' . DS . 'lib');

ini_set(
        'include_path'
        , ini_get('include_path') . PATH_SEPARATOR . CAKE_CORE_INCLUDE_PATH
);
if (!defined('CORE_PATH')) {
    define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
}

function cakephpRootTemporaryDirectory($uid) {
    if (is_int($uid)) {
        $userConfig = posix_getpwuid($uid);
    }
    else {
        $userConfig = posix_getpwnam($uid);
    }
    
    return (empty($userConfig['dir']) ? sys_get_temp_dir() : $userConfig['dir']) . DS . '.cakephp-tmp' . DS;
}

// Configuração de diretório de temporários por usuário.
function cakephpAppTemporaryDirectory($uid) {
    return cakephpRootTemporaryDirectory($uid) . (defined('APP_ID') ? APP_ID : 'undefined-app-id') . DS;
}

define('TMP', cakephpAppTemporaryDirectory(posix_getuid()));

function removeStringPrefix($prefix, $string) {
    if (substr($string, 0, strlen($prefix)) == $prefix) {
        return substr($string, strlen($prefix), strlen($string));
    } else {
        return $string;
    }
}

function checkTemporaryDirectory($tmpSkeletonRootPath, DirectoryIterator $tmpSkeletonSubDir) {
    while ($tmpSkeletonSubDir->valid()) {
        if ($tmpSkeletonSubDir->isDir() && !$tmpSkeletonSubDir->isDot()) {
            $relativePath = removeStringPrefix($tmpSkeletonRootPath, $tmpSkeletonSubDir->getRealPath());
            $fullPath = TMP . $relativePath;

            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            checkTemporaryDirectory($tmpSkeletonRootPath, new DirectoryIterator($tmpSkeletonSubDir->getRealPath()));
        }
        $tmpSkeletonSubDir->next();
    }
}

$tmpSkeletonRootPath = realpath(dirname(__FILE__) . DS . 'cakephp' . DS . 'app' . DS . 'tmp');
checkTemporaryDirectory(
    $tmpSkeletonRootPath
    , new DirectoryIterator($tmpSkeletonRootPath)
);

unset($userConfig);
unset($cakephpTmpRoot);
unset($appName);
unset($tmpSkeletonRootPath);