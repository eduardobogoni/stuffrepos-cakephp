<?php

class CommandLineUtil {

    public static function execute($command, $showOutput = false) {
        $command = self::_sanitizeCommand($command);
        if ($showOutput) {
            system($command, $result);
        } else {
            exec($command, $lines, $result);
        }
        if ($result) {
            throw new Exception("Command \"$command\" returned error code \"$result\"");
        }
    }

    private static function _sanitizeCommand($command) {
        if (is_array($command)) {
            return self::_buildCommandFromArray($command);
        } else {
            return escapeshellcmd($command);
        }
    }

    private static function _buildCommandFromArray($args) {
        $cmd = '';
        foreach ($args as $arg) {
            $cmd .= escapeshellarg($arg) . ' ';
        }
        return trim($cmd);
    }

}
