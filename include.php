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

// Configuração de diretório de temporários por usuário.
$userConfig = posix_getpwuid(posix_getuid());
$cakephpTmpRoot = (empty($userConfig['dir']) ? sys_get_temp_dir() : $userConfig['dir'])
        . DS . '.cakephp-tmp' . DS;
$appName = defined('APP_ID') ? APP_ID : 'undefined-app-id';
define('TMP', $cakephpTmpRoot . $appName . DS);

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